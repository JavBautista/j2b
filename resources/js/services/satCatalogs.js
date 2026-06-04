/**
 * Catálogos SAT fiscales (régimen, uso CFDI, matriz régimen→uso).
 * Fuente única: endpoint backend GET /admin/sat/fiscal-catalogs (tabla sat_* en BD).
 *
 * Estrategia de cache: memoria (por sesión) → localStorage con TTL → red.
 * Evita que cada componente tenga su propia copia hardcodeada del catálogo.
 *
 * NOTA: usa window.axios (global). NO importar axios aquí (rompe el bundle).
 */

const ENDPOINT = '/admin/sat/fiscal-catalogs';
const LS_KEY = 'sat_fiscal_catalogs_v2'; // v2: bundle incluye formas_pago/metodos_pago + aplica_emisor
const TTL_MS = 24 * 60 * 60 * 1000; // 24 h

let memoryCache = null;
let inflight = null;

function readLocalStorage() {
    try {
        const raw = localStorage.getItem(LS_KEY);
        if (!raw) return null;
        const parsed = JSON.parse(raw);
        if (parsed && parsed.ts && parsed.data && (Date.now() - parsed.ts) < TTL_MS) {
            return parsed.data;
        }
    } catch (e) { /* localStorage no disponible o JSON inválido */ }
    return null;
}

function writeLocalStorage(data) {
    try {
        localStorage.setItem(LS_KEY, JSON.stringify({ ts: Date.now(), data }));
    } catch (e) { /* quota / modo privado: ignorar */ }
}

/**
 * Devuelve una promesa con el bundle { regimenes, usos, matriz }.
 * regimenes/usos: [{ clave, nombre, aplica_fisica, aplica_moral }]
 * matriz: { [regimenClave]: [usoClave, ...] }
 */
export function loadFiscalCatalogs() {
    if (memoryCache) return Promise.resolve(memoryCache);

    const cached = readLocalStorage();
    if (cached) {
        memoryCache = cached;
        return Promise.resolve(memoryCache);
    }

    if (!inflight) {
        inflight = window.axios.get(ENDPOINT)
            .then(res => {
                memoryCache = res.data;
                writeLocalStorage(res.data);
                inflight = null;
                return memoryCache;
            })
            .catch(err => {
                inflight = null;
                throw err;
            });
    }
    return inflight;
}

/** Limpia el cache local (útil si el superadmin edita catálogos en esta sesión). */
export function clearFiscalCatalogsCache() {
    memoryCache = null;
    try { localStorage.removeItem(LS_KEY); } catch (e) { /* ignore */ }
}
