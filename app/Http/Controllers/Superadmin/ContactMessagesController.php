<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

class ContactMessagesController extends Controller
{
    /**
     * Vista principal de mensajes de contacto
     */
    public function index()
    {
        return view('superadmin.contact-messages.index');
    }

    /**
     * Obtener lista paginada de mensajes
     */
    public function get(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $query = ContactMessage::orderBy('created_at', 'desc');

        // Filtro por estado de lectura
        if ($request->filled('filter')) {
            if ($request->filter === 'unread') {
                $query->where('is_read', false);
            } elseif ($request->filter === 'read') {
                $query->where('is_read', true);
            }
        }

        // Búsqueda
        if ($request->filled('buscar') && $request->filled('criterio')) {
            $query->where($request->criterio, 'like', '%' . $request->buscar . '%');
        }

        $messages = $query->paginate(15);

        return [
            'pagination' => [
                'total' => $messages->total(),
                'current_page' => $messages->currentPage(),
                'per_page' => $messages->perPage(),
                'last_page' => $messages->lastPage(),
                'from' => $messages->firstItem(),
                'to' => $messages->lastItem(),
            ],
            'messages' => $messages->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'name' => $msg->name,
                    'email' => $msg->email,
                    'phone' => $msg->phone,
                    'formatted_phone' => $msg->formatted_phone,
                    'is_whatsapp' => $msg->is_whatsapp,
                    'whatsapp_link' => $msg->whatsapp_link,
                    'company' => $msg->company,
                    'message' => $msg->message,
                    'is_read' => $msg->is_read,
                    'read_at' => $msg->read_at?->format('d/m/Y H:i'),
                    'created_at' => $msg->created_at->format('d/m/Y H:i'),
                    'created_at_human' => $msg->created_at->diffForHumans(),
                ];
            }),
            'unread_count' => ContactMessage::unread()->count(),
        ];
    }

    /**
     * Obtener contador de mensajes no leídos
     */
    public function getUnreadCount(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        return [
            'unread_count' => ContactMessage::unread()->count(),
        ];
    }

    /**
     * Ver detalle de un mensaje
     */
    public function show(Request $request, $id)
    {
        if (!$request->ajax()) return redirect('/');

        $message = ContactMessage::findOrFail($id);

        return [
            'message' => [
                'id' => $message->id,
                'name' => $message->name,
                'email' => $message->email,
                'phone' => $message->phone,
                'formatted_phone' => $message->formatted_phone,
                'is_whatsapp' => $message->is_whatsapp,
                'whatsapp_link' => $message->whatsapp_link,
                'company' => $message->company,
                'message' => $message->message,
                'is_read' => $message->is_read,
                'read_at' => $message->read_at?->format('d/m/Y H:i'),
                'created_at' => $message->created_at->format('d/m/Y H:i'),
                'created_at_human' => $message->created_at->diffForHumans(),
                'ip_address' => $message->ip_address,
            ],
        ];
    }

    /**
     * Marcar mensaje como leído
     */
    public function markAsRead(Request $request, $id)
    {
        if (!$request->ajax()) return redirect('/');

        $message = ContactMessage::findOrFail($id);
        $message->markAsRead();

        return ['success' => true];
    }

    /**
     * Marcar mensaje como no leído
     */
    public function markAsUnread(Request $request, $id)
    {
        if (!$request->ajax()) return redirect('/');

        $message = ContactMessage::findOrFail($id);
        $message->markAsUnread();

        return ['success' => true];
    }

    /**
     * Marcar múltiples mensajes como leídos
     */
    public function markMultipleAsRead(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $ids = $request->input('ids', []);

        ContactMessage::whereIn('id', $ids)->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return ['success' => true];
    }

    /**
     * Eliminar mensaje
     */
    public function destroy(Request $request, $id)
    {
        if (!$request->ajax()) return redirect('/');

        $message = ContactMessage::findOrFail($id);
        $message->delete();

        return ['success' => true];
    }

    /**
     * Eliminar múltiples mensajes
     */
    public function destroyMultiple(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $ids = $request->input('ids', []);

        ContactMessage::whereIn('id', $ids)->delete();

        return ['success' => true];
    }
}
