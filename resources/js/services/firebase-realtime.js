import { initializeApp, getApps } from 'firebase/app';
import { getDatabase, ref, onValue, off } from 'firebase/database';

let firebaseApp = null;
let database = null;

function ensureInit() {
    if (database) return database;

    const config = window.j2bConfig?.firebase;

    if (!config || !config.apiKey || !config.databaseURL) {
        throw new Error('Firebase no configurado: window.j2bConfig.firebase ausente o incompleto');
    }

    firebaseApp = getApps().length ? getApps()[0] : initializeApp(config);
    database = getDatabase(firebaseApp);
    return database;
}

/**
 * Suscribe al nodo de tracking de una tarea en tiempo real.
 * @param {number} shopId
 * @param {number} taskId
 * @param {(data: object) => void} onData callback que recibe { metadata, last_position, points }
 * @param {(error: Error) => void} [onError]
 * @returns {() => void} función para desuscribir
 */
export function listenToTaskTracking(shopId, taskId, onData, onError) {
    const db = ensureInit();
    const path = `tracking/shop_${shopId}/tasks_active/task_${taskId}`;
    const taskRef = ref(db, path);

    const handler = onValue(
        taskRef,
        (snapshot) => {
            const data = snapshot.val() || {};
            onData(data);
        },
        (error) => {
            console.error('[Firebase] Error listening:', error);
            if (onError) onError(error);
        }
    );

    // Función de desuscripción
    return () => off(taskRef, 'value', handler);
}

/**
 * Convierte el objeto `points` de Firebase en un array ordenado por timestamp.
 * Firebase almacena: { point_{ts1}: {...}, point_{ts2}: {...} }
 */
export function pointsObjectToArray(pointsObj) {
    if (!pointsObj || typeof pointsObj !== 'object') return [];

    return Object.values(pointsObj)
        .filter(p => p && typeof p.lat !== 'undefined' && typeof p.lng !== 'undefined')
        .sort((a, b) => {
            const ta = new Date(a.timestamp).getTime();
            const tb = new Date(b.timestamp).getTime();
            return ta - tb;
        });
}
