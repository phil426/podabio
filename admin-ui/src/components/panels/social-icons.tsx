import { MusicNote, Heart, Newspaper } from '@phosphor-icons/react';
import { FaSpotify, FaYoutube, FaInstagram, FaTwitter, FaTiktok, FaFacebook, FaLinkedin, FaReddit, FaDiscord, FaTwitch, FaGithub, FaDribbble, FaMedium, FaSnapchat, FaPinterest, FaAmazon, FaPodcast } from 'react-icons/fa';

// Icon components using the uploaded SVG files
// These are loaded from /icons/ directory in the public folder
// Using inline SVG with currentColor for proper color inheritance
export const PocketCastsIcon = () => (
    <svg width="1em" height="1em" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" style={{ display: 'inline-block', verticalAlign: 'middle' }} aria-hidden="true">
        <circle cx="16" cy="15" r="15" fill="currentColor" opacity="0.1" />
        <path fillRule="evenodd" clipRule="evenodd" fill="currentColor" d="M16 32c8.837 0 16-7.163 16-16S24.837 0 16 0 0 7.163 0 16s7.163 16 16 16Zm0-28.444C9.127 3.556 3.556 9.127 3.556 16c0 6.873 5.571 12.444 12.444 12.444v-3.11A9.333 9.333 0 1 1 25.333 16h3.111c0-6.874-5.571-12.445-12.444-12.445ZM8.533 16A7.467 7.467 0 0 0 16 23.467v-2.715A4.751 4.751 0 1 1 20.752 16h2.715a7.467 7.467 0 0 0-14.934 0Z" />
    </svg>
);

export const CastroIcon = () => (
    <svg width="1em" height="1em" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" style={{ display: 'inline-block', verticalAlign: 'middle' }} aria-hidden="true">
        <path fill="currentColor" d="M16 0c-8.839 0-16 7.161-16 16s7.161 16 16 16c8.839 0 16-7.161 16-16s-7.161-16-16-16zM15.995 18.656c-3.645 0-3.645-5.473 0-5.473 3.651 0 3.651 5.473 0 5.473zM22.656 25.125l-2.683-3.719c5.303-3.876 2.553-12.267-4.009-12.256-6.568 0.016-9.281 8.417-3.964 12.271l-2.688 3.724c-3.995-2.891-5.676-8.025-4.161-12.719 1.521-4.687 5.891-7.869 10.823-7.864 6.277 0 11.365 5.088 11.365 11.364 0.005 3.641-1.735 7.063-4.683 9.199z" />
    </svg>
);

export const OvercastIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="1em" height="1em" style={{ display: 'inline-block', verticalAlign: 'middle' }} aria-hidden="true">
        <path fill="currentColor" fillRule="evenodd" d="M12 2.25A9.75 9.75 0 0 0 2.25 12a9.753 9.753 0 0 0 6.238 9.098l2.26 -7.538a2 2 0 1 1 2.502 0l2.262 7.538A9.753 9.753 0 0 0 21.75 12 9.75 9.75 0 0 0 12 2.25Zm0 19.5a9.788 9.788 0 0 1 -2.076 -0.221l0.078 -0.258L12 19.473l1.998 1.798 0.078 0.258A9.788 9.788 0 0 1 12 21.75ZM0.75 12C0.75 5.787 5.787 0.75 12 0.75S23.25 5.787 23.25 12 18.213 23.25 12 23.25 0.75 18.213 0.75 12Zm12.695 7.428 -0.698 -0.628 0.402 -0.361 0.296 0.99ZM12 18.128l0.83 -0.748 -0.83 -2.77 -0.83 2.77 0.83 0.747Zm-1.445 1.3 0.698 -0.628 -0.402 -0.361 -0.296 0.99ZM6.95 6.9a0.75 0.75 0 0 1 0.15 1.05c-0.44 0.586 -1.35 2.265 -1.35 4.05 0 1.785 0.91 3.464 1.35 4.05a0.75 0.75 0 1 1 -1.2 0.9c-0.56 -0.747 -1.65 -2.735 -1.65 -4.95 0 -2.215 1.09 -4.203 1.65 -4.95a0.75 0.75 0 0 1 1.05 -0.15Zm2.08 2.07a0.75 0.75 0 0 1 0 1.06c-0.238 0.238 -0.78 1.025 -0.78 1.97 0 0.945 0.542 1.732 0.78 1.97a0.75 0.75 0 1 1 -1.06 1.06c-0.43 -0.428 -1.22 -1.575 -1.22 -3.03 0 -1.455 0.79 -2.602 1.22 -3.03a0.75 0.75 0 0 1 1.06 0Zm9.07 -1.92a0.75 0.75 0 0 0 -1.2 0.9c0.44 0.586 1.35 2.265 1.35 4.05 0 1.785 -0.91 3.464 -1.35 4.05a0.75 0.75 0 1 0 1.2 0.9c0.56 -0.747 1.65 -2.735 1.65 -4.95 0 -2.215 -1.09 -4.203 -1.65 -4.95Zm-3.13 1.92a0.75 0.75 0 0 1 1.06 0c0.43 0.428 1.22 1.575 1.22 3.03 0 1.455 -0.79 2.602 -1.22 3.03a0.75 0.75 0 1 1 -1.06 -1.06c0.238 -0.238 0.78 -1.025 0.78 -1.97 0 -0.945 -0.542 -1.732 -0.78 -1.97a0.75 0.75 0 0 1 0 -1.06Z" clipRule="evenodd" />
    </svg>
);

// Platform icon mapping with specific brand icons
export const getPlatformIcon = (platformName: string): JSX.Element => {
    const iconMap: Record<string, JSX.Element> = {
        // Podcast Platforms
        apple_podcasts: <FaPodcast aria-hidden="true" />,
        spotify: <FaSpotify aria-hidden="true" />,
        youtube_music: <FaYoutube aria-hidden="true" />,
        iheart_radio: <Heart aria-hidden="true" size={20} weight="regular" />,
        amazon_music: <FaAmazon aria-hidden="true" />,
        pocket_casts: <PocketCastsIcon />,
        castro: <CastroIcon />,
        overcast: <OvercastIcon />,
        // Video/Social
        youtube: <FaYoutube aria-hidden="true" />,
        instagram: <FaInstagram aria-hidden="true" />,
        twitter: <FaTwitter aria-hidden="true" />,
        tiktok: <FaTiktok aria-hidden="true" />,
        substack: <Newspaper aria-hidden="true" size={20} weight="regular" />,
        // Social/Professional
        facebook: <FaFacebook aria-hidden="true" />,
        linkedin: <FaLinkedin aria-hidden="true" />,
        reddit: <FaReddit aria-hidden="true" />,
        discord: <FaDiscord aria-hidden="true" />,
        // Specialized
        twitch: <FaTwitch aria-hidden="true" />,
        github: <FaGithub aria-hidden="true" />,
        dribbble: <FaDribbble aria-hidden="true" />,
        medium: <FaMedium aria-hidden="true" />,
        snapchat: <FaSnapchat aria-hidden="true" />,
        pinterest: <FaPinterest aria-hidden="true" />
    };
    return iconMap[platformName] || <MusicNote aria-hidden="true" size={20} weight="regular" />;
};
