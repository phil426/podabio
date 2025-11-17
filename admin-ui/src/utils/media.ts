export function getYouTubeThumbnail(urlOrId: string | null | undefined): string | null {
  if (!urlOrId) return null;
  const input = urlOrId.trim();
  if (input === '') return null;

  const idMatch = input.match(
    /(?:youtube\.com\/(?:[^/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?/\s]{11})/
  );

  let videoId = input;
  if (idMatch && idMatch[1]) {
    videoId = idMatch[1];
  }

  if (!/^[a-zA-Z0-9_-]{11}$/.test(videoId)) {
    return null;
  }

  return `https://img.youtube.com/vi/${videoId}/hqdefault.jpg`;
}

