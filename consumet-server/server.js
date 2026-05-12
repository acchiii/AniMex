import express from 'express';
import cors from 'cors';
import { ANIME } from '@consumet/extensions';

const app = express();
app.use(cors());
app.use(express.json());

const PORT = process.env.PORT || 3000;
// Hianime (formerly Gogoanime) — best subtitle support, multiple servers, HD, fastest
// AnimePahe — cleaner videos, smaller file sizes
// Fallbacks (stable, wide library)
const PROVIDER_PRIORITY = ['Hianime', 'AnimePahe', 'AnimeSaturn', 'AnimeUnity', 'AnimeKai', 'KickAssAnime', 'AnimeSama'];

function createProvider(name) {
  if (!ANIME[name]) {
    throw new Error(`Unknown provider: ${name}`);
  }
  return new ANIME[name]();
}

function withTimeout(promise, ms, label) {
  return Promise.race([
    promise,
    new Promise((_, reject) =>
      setTimeout(() => reject(new Error(label ? `${label}: Timed out after ${ms}ms` : `Timed out after ${ms}ms`)), ms)
    ),
  ]);
}

const anilistGraphqlUrl = 'https://graphql.anilist.co';

async function fetchAnilistMedia(anilistId) {
  const query = `
    query ($id: Int) {
      Media(id: $id, type: ANIME) {
        id
        title { romaji english native }
        synonyms
        episodes
        status
        startDate { year month day }
        genres
      }
    }
  `;

  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), 5000);

  const resp = await fetch(anilistGraphqlUrl, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
    body: JSON.stringify({ query, variables: { id: anilistId } }),
    signal: controller.signal,
  });

  clearTimeout(timeoutId);

  if (!resp.ok) {
    throw new Error(`AniList API error: ${resp.status}`);
  }

  const json = await resp.json();
  return json.data?.Media;
}

async function searchOnProvider(provider, titles) {
  const seen = new Set();

  for (const title of titles) {
    if (!title || seen.has(title)) continue;
    seen.add(title);

    try {
      const searchResult = await provider.search(title);
      const results = searchResult?.results || [];
      if (results.length > 0) {
        return results[0];
      }
    } catch (e) {
      continue;
    }
  }

  return null;
}

async function tryProviders(anilistId, titles) {
  const media = await fetchAnilistMedia(anilistId);
  if (!media) {
    throw new Error(`AniList media not found for ID ${anilistId}`);
  }

  const searchTitles = [
    media.title?.english,
    media.title?.romaji,
    ...(media.synonyms || []),
    media.title?.native,
  ].filter(Boolean);

  const allTitles = [...new Set([...searchTitles, ...titles])];

  const PROVIDER_TIMEOUT = 20000;

  const promises = PROVIDER_PRIORITY.map(async (providerName) => {
    const provider = createProvider(providerName);
    const found = await withTimeout(searchOnProvider(provider, allTitles), PROVIDER_TIMEOUT, providerName);
    if (!found) throw new Error(`${providerName}: no match found`);
    const info = await withTimeout(provider.fetchAnimeInfo(found.id), PROVIDER_TIMEOUT, providerName);
    return { provider: providerName, anime: info };
  });

  return new Promise((resolve, reject) => {
    let settled = false;
    const errors = [];

    for (const promise of promises) {
      promise
        .then((result) => {
          if (!settled) {
            settled = true;
            resolve(result);
          }
        })
        .catch((err) => {
          errors.push(err.message);
          if (errors.length === promises.length) {
            reject(
              new Error(
                `No provider found for AniList ID ${anilistId}. Errors: ${errors.join('; ')}`
              )
            );
          }
        });
    }
  });
}

// GET /meta/anilist/info/:id
app.get('/meta/anilist/info/:id', async (req, res) => {
  try {
    const anilistId = parseInt(req.params.id);
    if (!anilistId || anilistId <= 0) {
      return res.status(400).json({ error: 'Invalid AniList ID' });
    }

    const title = req.query.title || '';
    const titles = title ? [title] : [];

    const result = await tryProviders(anilistId, titles);

    const episodes = (result.anime.episodes || []).map((ep, i) => ({
      id: ep.id,
      number: ep.number || i + 1,
      title: ep.title || null,
      image: ep.image || null,
      description: ep.description || null,
      releaseDate: ep.releaseDate || null,
      url: ep.url || null,
    }));

    res.json({
      id: result.anime.id,
      title: result.anime.title,
      malId: result.anime.malId,
      anilistId,
      image: result.anime.image,
      cover: result.anime.cover,
      description: result.anime.description,
      status: result.anime.status,
      type: result.anime.type,
      releaseDate: result.anime.releaseDate,
      genres: result.anime.genres,
      rating: result.anime.rating,
      duration: result.anime.duration,
      studios: result.anime.studios,
      totalEpisodes: result.anime.totalEpisodes || result.anime.episodes?.length || episodes.length,
      episodes,
      _provider: result.provider,
    });
  } catch (e) {
    console.error('/meta/anilist/info error:', e.message);
    res.status(500).json({ error: e.message });
  }
});

// GET /meta/anilist/watch/:episodeId
app.get('/meta/anilist/watch/:episodeId', async (req, res) => {
  try {
    const { episodeId } = req.params;
    let providerName = req.query.provider || null;

    if (!episodeId) {
      return res.status(400).json({ error: 'Missing episodeId' });
    }

    let sources = null;

    const PROVIDER_TIMEOUT = 20000;

    const tryProvider = async (name) => {
      const provider = createProvider(name);
      const result = await withTimeout(provider.fetchEpisodeSources(episodeId), PROVIDER_TIMEOUT, name);
      if (result?.sources?.length > 0) return { providerName: name, sources: result };
      throw new Error(`${name}: no sources`);
    };

    const tryAllProviders = async () => {
      const promises = PROVIDER_PRIORITY.map((name) => tryProvider(name));
      try {
        return await Promise.any(promises);
      } catch {
        return null;
      }
    };

    if (providerName) {
      try {
        const result = await tryProvider(providerName);
        providerName = result.providerName;
        sources = result.sources;
      } catch {
        const result = await tryAllProviders();
        if (result) {
          providerName = result.providerName;
          sources = result.sources;
        }
      }
    } else {
      const result = await tryAllProviders();
      if (result) {
        providerName = result.providerName;
        sources = result.sources;
      }
    }

    if (!sources || !sources.sources || sources.sources.length === 0) {
      return res.status(404).json({ error: 'No sources found' });
    }

    const normalizedSources = (sources.sources || []).map((s) => ({
      url: s.url,
      quality: s.quality || 'default',
      isM3U8: !!s.isM3U8,
      isDASH: !!s.isDASH,
    }));

    const normalizedSubtitles = (sources.subtitles || []).map((s) => ({
      url: s.url,
      lang: s.lang || 'en',
    }));

    res.json({
      sources: normalizedSources,
      subtitles: normalizedSubtitles,
      headers: sources.headers || {},
      download: sources.download || null,
      _provider: providerName,
    });
  } catch (e) {
    console.error('/meta/anilist/watch error:', e.message);
    res.status(500).json({ error: e.message });
  }
});

// GET /health
app.get('/health', (req, res) => {
  res.json({ status: 'ok', providers: PROVIDER_PRIORITY });
});

app.listen(PORT, '0.0.0.0', () => {
  console.log(`Consumet API server running on http://0.0.0.0:${PORT}`);
  console.log(`Providers: ${PROVIDER_PRIORITY.join(', ')}`);
});
