// Espejo del catálogo en app/Support/SatCatalogos/Bancos.php
// Si actualizas uno, actualiza el otro.
// Última verificación: 2026-04-29

export const BANCOS = [
    { code: '002', name: 'Banamex (Citibanamex)', rfc: 'BNM840515VB1' },
    { code: '012', name: 'BBVA México',           rfc: 'BBA830831LJ2' },
    { code: '014', name: 'Santander',             rfc: 'BSM970519DU8' },
    { code: '021', name: 'HSBC',                  rfc: 'HMI950125KG8' },
    { code: '044', name: 'Scotiabank',            rfc: 'SIN9412025I4' },
    { code: '072', name: 'Banorte',               rfc: 'BMN930209927' },
    { code: '999', name: 'Otro / Extranjero',     rfc: '' },
];

export const BANCOS_CODES = BANCOS.map(b => b.code);

export function findBanco(code) {
    return BANCOS.find(b => b.code === code) || null;
}
