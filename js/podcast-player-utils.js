// Podcast Player Utility Functions

/**
 * Format seconds to MM:SS or HH:MM:SS
 */
function formatTime(seconds) {
    if (!isFinite(seconds) || isNaN(seconds) || seconds < 0) {
        return '0:00';
    }
    
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = Math.floor(seconds % 60);
    
    if (hours > 0) {
        return `${hours}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }
    return `${minutes}:${String(secs).padStart(2, '0')}`;
}

/**
 * Format date to relative time or absolute date
 */
function formatDate(dateString) {
    if (!dateString) return '';
    
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) {
        return 'Just now';
    }
    
    const diffInMinutes = Math.floor(diffInSeconds / 60);
    if (diffInMinutes < 60) {
        return `${diffInMinutes} minute${diffInMinutes > 1 ? 's' : ''} ago`;
    }
    
    const diffInHours = Math.floor(diffInMinutes / 60);
    if (diffInHours < 24) {
        return `${diffInHours} hour${diffInHours > 1 ? 's' : ''} ago`;
    }
    
    const diffInDays = Math.floor(diffInHours / 24);
    if (diffInDays < 7) {
        return `${diffInDays} day${diffInDays > 1 ? 's' : ''} ago`;
    }
    
    const diffInWeeks = Math.floor(diffInDays / 7);
    if (diffInWeeks < 4) {
        return `${diffInWeeks} week${diffInWeeks > 1 ? 's' : ''} ago`;
    }
    
    // Return formatted date for older items
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined });
}

/**
 * Parse duration string (HH:MM:SS or MM:SS) to seconds
 */
function parseDuration(duration) {
    if (!duration) return null;
    
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
 * LocalStorage helpers (namespaced)
 */
const PodcastStorage = {
    get: (key, defaultValue = null) => {
        try {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : defaultValue;
        } catch (e) {
            return defaultValue;
        }
    },
    
    set: (key, value) => {
        try {
            localStorage.setItem(key, JSON.stringify(value));
            return true;
        } catch (e) {
            return false;
        }
    },
    
    remove: (key) => {
        try {
            localStorage.removeItem(key);
            return true;
        } catch (e) {
            return false;
        }
    }
};

/**
 * Show toast notification (scoped to drawer)
 */
function showToast(message, type = 'info', drawerContainer = null) {
    const container = drawerContainer || document.querySelector('.podcast-top-drawer');
    if (!container) return;
    
    let toast = container.querySelector('.toast');
    let toastMessage = container.querySelector('#toast-message');
    
    if (!toast) {
        toast = document.createElement('div');
        toast.className = 'toast';
        toast.id = 'toast';
        toast.style.display = 'none';
        container.appendChild(toast);
    }
    
    if (!toastMessage) {
        toastMessage = document.createElement('span');
        toastMessage.id = 'toast-message';
        toast.appendChild(toastMessage);
    }
    
    toast.className = `toast ${type}`;
    toastMessage.textContent = message;
    toast.style.display = 'block';
    
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => {
            toast.style.display = 'none';
            toast.style.opacity = '1';
        }, 300);
    }, 3000);
}

/**
 * Debounce function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Throttle function
 */
function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Extract dominant color from image (simple approximation)
 */
function getDominantColor(imageUrl, callback) {
    const img = new Image();
    img.crossOrigin = 'anonymous';
    
    img.onload = function() {
        const canvas = document.createElement('canvas');
        canvas.width = img.width;
        canvas.height = img.height;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0);
        
        // Simple color extraction (get pixel from center)
        const centerX = Math.floor(img.width / 2);
        const centerY = Math.floor(img.height / 2);
        const imageData = ctx.getImageData(centerX, centerY, 1, 1);
        const [r, g, b] = imageData.data;
        
        // Convert to hex
        const hex = '#' + [r, g, b].map(x => {
            const hex = x.toString(16);
            return hex.length === 1 ? '0' + hex : hex;
        }).join('');
        
        callback(hex);
    };
    
    img.onerror = () => callback(null);
    img.src = imageUrl;
}

/**
 * Smooth scroll to element
 */
function scrollToElement(element, offset = 0) {
    if (!element) return;
    
    const elementPosition = element.getBoundingClientRect().top + window.pageYOffset;
    const offsetPosition = elementPosition - offset;
    
    window.scrollTo({
        top: offsetPosition,
        behavior: 'smooth'
    });
}

/**
 * Check if element is in viewport
 */
function isInViewport(element) {
    const rect = element.getBoundingClientRect();
    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
}

/**
 * Create element with attributes
 */
function createElement(tag, attributes = {}, textContent = '') {
    const element = document.createElement(tag);
    
    Object.keys(attributes).forEach(key => {
        if (key === 'className') {
            element.className = attributes[key];
        } else if (key === 'dataset') {
            Object.keys(attributes[key]).forEach(dataKey => {
                element.dataset[dataKey] = attributes[key][dataKey];
            });
        } else {
            element.setAttribute(key, attributes[key]);
        }
    });
    
    if (textContent) {
        element.textContent = textContent;
    }
    
    return element;
}

/**
 * Get proxied image URL (for CORS handling)
 * Updated to accept imageProxyUrl as parameter instead of using CONFIG
 */
function getProxiedImageUrl(imageUrl, imageProxyUrl = '/api/podcast-image-proxy.php') {
    if (!imageUrl) return '';
    
    // If already proxied or relative URL, return as-is
    if (imageUrl.includes('image-proxy.php') || imageUrl.startsWith('/') || imageUrl.startsWith('./')) {
        return imageUrl;
    }
    
    // Use image proxy
    if (imageProxyUrl) {
        return imageProxyUrl + '?url=' + encodeURIComponent(imageUrl);
    }
    
    return imageUrl;
}

