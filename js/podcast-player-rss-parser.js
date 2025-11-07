// RSS Parser - Client-side RSS feed parsing

class PodcastRSSParser {
    constructor(rssProxyUrl) {
        this.rssProxyUrl = rssProxyUrl || '/api/rss-proxy.php';
    }

    /**
     * Fetch RSS feed (via proxy or direct)
     */
    async fetchFeed(url) {
        try {
            // Try using proxy first (for CORS handling)
            const proxyUrl = this.rssProxyUrl + '?url=' + encodeURIComponent(url);
            const response = await fetch(proxyUrl);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const text = await response.text();
            return text;
        } catch (error) {
            // Fallback to direct fetch if proxy fails
            console.warn('Proxy fetch failed, trying direct fetch:', error);
            try {
                const response = await fetch(url);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const text = await response.text();
                return text;
            } catch (directError) {
                throw new Error(`Failed to fetch RSS feed: ${directError.message}`);
            }
        }
    }

    /**
     * Parse RSS XML string
     */
    async parseFeed(feedUrl) {
        try {
            const xmlText = await this.fetchFeed(feedUrl);
            const parser = new DOMParser();
            const xmlDoc = parser.parseFromString(xmlText, 'text/xml');
            
            // Check for parsing errors
            const parseError = xmlDoc.querySelector('parsererror');
            if (parseError) {
                throw new Error('Failed to parse RSS feed XML');
            }
            
            return this.parseXML(xmlDoc);
        } catch (error) {
            console.error('RSS parsing error:', error);
            throw error;
        }
    }

    /**
     * Parse XML document into structured data
     */
    parseXML(xmlDoc) {
        const data = {
            title: '',
            description: '',
            coverImage: '',
            episodes: []
        };

        // Check if it's RSS 2.0
        const channel = xmlDoc.querySelector('channel');
        if (channel) {
            // Parse podcast metadata
            data.title = this.getTextContent(channel, 'title') || '';
            data.description = this.getTextContent(channel, 'description') || '';
            
            // Get cover image (iTunes namespace)
            const itunesImage = channel.querySelector('itunes\\:image, image[href]');
            if (itunesImage) {
                data.coverImage = itunesImage.getAttribute('href') || '';
            } else {
                const image = channel.querySelector('image url');
                if (image) {
                    data.coverImage = this.getTextContent(channel, 'image > url') || '';
                }
            }
            
            // Parse episodes
            const items = channel.querySelectorAll('item');
            items.forEach((item, index) => {
                const episode = this.parseEpisode(item);
                if (episode) {
                    data.episodes.push(episode);
                }
            });
        } else {
            // Check if it's Atom feed
            const entries = xmlDoc.querySelectorAll('entry');
            if (entries.length > 0) {
                data.title = this.getTextContent(xmlDoc.documentElement, 'title') || '';
                data.description = this.getTextContent(xmlDoc.documentElement, 'subtitle') || '';
                
                entries.forEach(entry => {
                    const episode = this.parseAtomEntry(entry);
                    if (episode) {
                        data.episodes.push(episode);
                    }
                });
            }
        }

        return data;
    }

    /**
     * Parse RSS item (episode)
     */
    parseEpisode(item) {
        const episode = {
            title: this.getTextContent(item, 'title') || 'Untitled Episode',
            description: this.getTextContent(item, 'description') || '',
            pubDate: this.getTextContent(item, 'pubDate') || '',
            audioUrl: '',
            duration: null,
            guid: this.getTextContent(item, 'guid') || '',
            artwork: '',
            chapters: []
        };

        // Get audio URL from enclosure
        const enclosure = item.querySelector('enclosure');
        if (enclosure) {
            episode.audioUrl = enclosure.getAttribute('url') || '';
            const type = enclosure.getAttribute('type') || '';
            if (!type.startsWith('audio/') && !episode.audioUrl) {
                // Try to find link if no enclosure
                const link = this.getTextContent(item, 'link');
                if (link) {
                    episode.audioUrl = link;
                }
            }
        } else {
            const link = this.getTextContent(item, 'link');
            if (link) {
                episode.audioUrl = link;
            }
        }

        // Get duration (iTunes)
        const duration = this.getTextContent(item, 'itunes\\:duration');
        if (duration) {
            episode.duration = this.parseDurationString(duration);
        }

        // Get artwork (iTunes)
        const itunesImage = item.querySelector('itunes\\:image');
        if (itunesImage) {
            episode.artwork = itunesImage.getAttribute('href') || '';
        }

        // Parse chapters (podcast:chapters)
        const chaptersJson = this.getTextContent(item, 'podcast\\:chapters, chapters');
        if (chaptersJson) {
            try {
                const chaptersData = JSON.parse(chaptersJson);
                if (chaptersData.chapters && Array.isArray(chaptersData.chapters)) {
                    episode.chapters = chaptersData.chapters.map(ch => ({
                        title: ch.title || '',
                        startTime: ch.startTime || 0,
                        imageUrl: ch.img || null,
                        url: ch.url || null
                    }));
                }
            } catch (e) {
                console.warn('Failed to parse chapters JSON:', e);
            }
        }

        // Fallback: try to parse chapters from content:encoded if available
        if (episode.chapters.length === 0) {
            const contentEncoded = this.getTextContent(item, 'content\\:encoded');
            if (contentEncoded) {
                episode.chapters = this.extractChaptersFromContent(contentEncoded);
            }
        }

        // Skip if no audio URL
        if (!episode.audioUrl) {
            return null;
        }

        return episode;
    }

    /**
     * Parse Atom entry (episode)
     */
    parseAtomEntry(entry) {
        const episode = {
            title: this.getTextContent(entry, 'title') || 'Untitled Episode',
            description: this.getTextContent(entry, 'summary') || '',
            pubDate: this.getTextContent(entry, 'published') || this.getTextContent(entry, 'updated') || '',
            audioUrl: '',
            duration: null,
            guid: this.getTextContent(entry, 'id') || '',
            artwork: '',
            chapters: []
        };

        // Get audio URL from links
        const links = entry.querySelectorAll('link');
        links.forEach(link => {
            const type = link.getAttribute('type') || '';
            if (type.startsWith('audio/') || type.startsWith('video/')) {
                episode.audioUrl = link.getAttribute('href') || '';
            }
        });

        if (!episode.audioUrl) {
            return null;
        }

        return episode;
    }

    /**
     * Get text content from element
     */
    getTextContent(element, selector) {
        const el = element.querySelector(selector);
        return el ? el.textContent.trim() : '';
    }

    /**
     * Parse duration string to seconds
     */
    parseDurationString(duration) {
        if (!duration) return null;
        
        // Handle formats like "01:23:45", "83:45", or "5025"
        const parts = duration.split(':').map(Number);
        
        if (parts.length === 3) {
            return parts[0] * 3600 + parts[1] * 60 + parts[2];
        } else if (parts.length === 2) {
            return parts[0] * 60 + parts[1];
        } else if (parts.length === 1) {
            return parts[0];
        }
        
        return null;
    }

    /**
     * Extract chapters from HTML content (fallback method)
     */
    extractChaptersFromContent(content) {
        const chapters = [];
        const parser = new DOMParser();
        const doc = parser.parseFromString(content, 'text/html');
        
        // Look for timestamp links or patterns like [1:23] or 1:23 - Chapter Title
        const links = doc.querySelectorAll('a[href*="#t="], a[href*="?t="]');
        links.forEach(link => {
            const href = link.getAttribute('href') || '';
            const match = href.match(/[#?]t=(\d+)/);
            if (match) {
                const startTime = parseInt(match[1]);
                chapters.push({
                    title: link.textContent.trim() || 'Chapter',
                    startTime: startTime,
                    imageUrl: null,
                    url: null
                });
            }
        });
        
        // Sort by start time
        chapters.sort((a, b) => a.startTime - b.startTime);
        
        return chapters;
    }
}

