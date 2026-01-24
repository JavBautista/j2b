<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RentDetail;
use App\Models\RentDetailImage;
use Illuminate\Support\Facades\Storage;
use App\Services\ImageService;

class EquipmentController extends Controller
{
    public function index(Request $request){
        $user = $request->user();
        $shop = $user->shop;

        $buscar = $request->buscar;

        // Inicializamos la consulta base
        $query = RentDetail::with('images')
            ->where('active', 1)
            ->where('rent_id', 0)
            ->where('shop_id', $shop->id);

        // Aplicar búsqueda si existe
        if (!empty($buscar)) {
            $terms = explode(' ', $buscar); // Dividir la búsqueda en palabras
            $query->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $q->where(function ($subQuery) use ($term) {
                        $subQuery->where('trademark', 'like', "%$term%")
                                 ->orWhere('model', 'like', "%$term%")
                                 ->orWhere('serial_number', 'like', "%$term%");
                    });
                }
            });
        }

        // Obtener los resultados paginados
        $equipments = $query->orderBy('id', 'desc')->paginate(10);

        return $equipments;
    }//index()
    
    public function store(Request $request){
        
        $user = $request->user();
        $shop = $user->shop;

        $now = now();

        $equipment = new RentDetail();
        $equipment->active = 1;
        $equipment->rent_id = 0;
        $equipment->shop_id = $shop->id;
        $equipment->trademark = $request->trademark;
        $equipment->model = $request->model;
        $equipment->serial_number = $request->serial_number;
        $equipment->rent_price = $request->rent_price;
        $equipment->monochrome = $request->monochrome;
        $equipment->pages_included_mono = $request->pages_included_mono;
        $equipment->extra_page_cost_mono = $request->extra_page_cost_mono;
        $equipment->counter_mono = $request->counter_mono;
        $equipment->update_counter_mono = $now;
        $equipment->color = $request->color;
        $equipment->pages_included_color = $request->pages_included_color;
        $equipment->extra_page_cost_color = $request->extra_page_cost_color;
        $equipment->counter_color = $request->counter_color;
        $equipment->update_counter_color = $now;

        $equipment->description = $request->description;
        $equipment->cost = $request->cost;
        $equipment->retail = $request->retail;
        $equipment->wholesale = $request->wholesale;
        $equipment->type_sale = $request->type_sale;

        $equipment->save();

        return response()->json([
            'ok'=>true,
            'equipment' => $equipment,
        ]);
    }//.store

    public function update(Request $request){
        $equipment = RentDetail::findOrFail($request->id);
        $now = now();
        $equipment->trademark = $request->trademark;
        $equipment->model    = $request->model;
        $equipment->serial_number = $request->serial_number;
        $equipment->rent_price = $request->rent_price;
        $equipment->monochrome = $request->monochrome;
        $equipment->pages_included_mono = $request->pages_included_mono;
        $equipment->extra_page_cost_mono = $request->extra_page_cost_mono;
        $equipment->counter_mono = $request->counter_mono;
        $equipment->update_counter_mono = $now;
        $equipment->color = $request->color;
        $equipment->pages_included_color = $request->pages_included_color;
        $equipment->extra_page_cost_color = $request->extra_page_cost_color;
        $equipment->counter_color = $request->counter_color;
        $equipment->update_counter_color = $now;

        $equipment->description = $request->description;
        $equipment->cost = $request->cost;
        $equipment->retail = $request->retail;
        $equipment->wholesale = $request->wholesale;
        $equipment->type_sale = $request->type_sale;

        $equipment->save();
        return response()->json([
            'ok'=>true,
            'equipment' => $equipment,
        ]);
    }//.update
    
    public function active(Request $request){
        $equipment = RentDetail::findOrFail($request->id);
        $equipment->active = 1;
        $equipment->save();
        return response()->json([
            'ok'=>true,
            'equipment' => $equipment,
        ]);
    }//.active
    public function inactive(Request $request){
        $equipment = RentDetail::findOrFail($request->id);
        $equipment->active = 0;
        $equipment->save();
        return response()->json([
            'ok'=>true,
            'equipment' => $equipment,
        ]);
    }//.inactive

    public function destroy(Request $request){
        $equipment = RentDetail::findOrFail($request->id);
        // Eliminar los registros relacionados en 'consumables'
        $equipment->consumables()->delete();
        $equipment->delete();
        return response()->json([
            'ok'=>true
        ]);
    }//.destroy


    /*NUEVOS METODOS PARA SUBIR IMAGENES AL RENT DETAIL (EQUIPOS)*/
    public function uploadImage(Request $request){

        $rent_detail_id = $request->equipment_id;
        // Validar la existencia del archivo de imagen
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            // Procesar y optimizar la imagen
            $imageService = new ImageService();
            $imagePath = $imageService->processAndStore($image, 'equipments');

            $rent_detail_image = new RentDetailImage();
            $rent_detail_image->rent_detail_id = $rent_detail_id;
            $rent_detail_image->image = $imagePath;
            $rent_detail_image->save();

            $rent_detail = RentDetail::findOrFail($rent_detail_id);
            $rent_detail->load('images');
            return response()->json([
                'ok'=>true,
                'equipment' => $rent_detail,
            ]);
        }

        return response()->json([
            'ok'=>false
        ]);

    }//uploadImage()

    public function deleteImage(Request $request){
        $rent_detail_id = $request->input('equipment.id');// Obtener el 'id' del rent_detail del request
        $img_id         = $request->input('img_id');// Obtener el 'img_alt_id' del request

        try {
            // Buscar la imagen por su ID
            $rent_detail_image = RentDetailImage::findOrFail($img_id);

            // Verificar si la imagen pertenece al rent_detail indicado
            if ($rent_detail_image->rent_detail_id != $rent_detail_id) {
                return response()->json([
                    'ok' => false,
                    'message' => 'La imagen no pertenece al equipo indicado.',
                ], 400);
            }

            // Eliminar la imagen del almacenamiento
            Storage::disk('public')->delete($rent_detail_image->image);

            // Eliminar el registro de la imagen de la base de datos
            $rent_detail_image->delete();

            // Cargar el rent_detail con las relaciones actualizadas
            $rent_detail = RentDetail::with('images')->findOrFail($rent_detail_id);

            return response()->json([
                'ok' => true,
                'equipment' => $rent_detail,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al eliminar la imagen.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }//.deleteImage()

    public function updateUrlWebMonitor(Request $request){
        $equipment = RentDetail::findOrFail($request->id);
        $equipment->url_web_monitor = $request->url_web_monitor;
        $equipment->save();
        return response()->json([
            'ok'=>true,
            'equipment' => $equipment,
        ]);
    }//.updateUrlWebMonitor
}
