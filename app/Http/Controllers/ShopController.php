<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shop;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Services\ImageService;

class ShopController extends Controller
{
    public function getShop(Request $request){
       $user = $request->user();
       $shop = $user->shop;
       $shop= Shop::findOrFail($shop->id);
       return $shop;
    }



    public function update(Request $request)
    {
        $wa = (isset($request['whatsapp']))?$request['whatsapp']:0;

        $shop= Shop::findOrFail($request->id);
        $shop->description = $request->description;
        $shop->zip_code = $request->zip_code;
        $shop->address = $request->address;
        $shop->number_out = $request->number_out;
        $shop->number_int = $request->number_int;
        $shop->district = $request->district;
        $shop->city = $request->city;
        $shop->state = $request->state;
        $shop->whatsapp = $wa;
        $shop->phone = $request->phone;
        $shop->email = $request->email;
        $shop->bank_name = $request->bank_name;
        $shop->bank_number = $request->bank_number;
        $shop->web = $request->web;
        $shop->facebook = $request->facebook;
        $shop->twitter = $request->twitter;
        $shop->instagram = $request->instagram;
        $shop->pinterest = $request->pinterest;
        $shop->video_channel = $request->video_channel;
        $shop->slogan = $request->slogan;
        $shop->presentation = $request->presentation;
        $shop->mission = $request->mission;
        $shop->vision = $request->vision;
        $shop->values = $request->values;
        $shop->bank_number_secondary = $request->bank_number_secondary;
        $shop->owner_name = $request->owner_name;
        $shop->save();

        return response()->json([
            'ok'=>true,
            'shop' => $shop,
        ]);
    }

    public function updateWeb(Request $request)
    {
        $wa = (isset($request['whatsapp']))?$request['whatsapp']:0;

        $shop= Shop::findOrFail($request->id);

        $shop->description = $request->description;
        $shop->zip_code = $request->zip_code;
        $shop->address = $request->address;
        $shop->number_out = $request->number_out;
        $shop->number_int = $request->number_int;
        $shop->district = $request->district;
        $shop->city = $request->city;
        $shop->state = $request->state;
        $shop->whatsapp = $wa;
        $shop->phone = $request->phone;
        $shop->email = $request->email;
        $shop->bank_name = $request->bank_name;
        $shop->bank_number = $request->bank_number;
        $shop->web = $request->web;
        $shop->facebook = $request->facebook;
        $shop->twitter = $request->twitter;
        $shop->instagram = $request->instagram;
        $shop->pinterest = $request->pinterest;
        $shop->video_channel = $request->video_channel;
        $shop->slogan = $request->slogan;
        $shop->presentation = $request->presentation;
        $shop->mission = $request->mission;
        $shop->vision = $request->vision;
        $shop->values = $request->values;
        $shop->bank_number_secondary = $request->bank_number_secondary;
        $shop->owner_name = $request->owner_name;
        $shop->save();

        return redirect()->route('admin.shop')->with('success', 'Información de la tienda actualizada exitosamente');
    }

    public function updateSignature(Request $request, Shop $shop)
    {
        // Validar que el usuario tenga acceso a esta tienda
        if ($shop->id !== Auth::user()->shop->id) {
            abort(403, 'No tienes permisos para modificar esta tienda');
        }

        // Validar archivo de firma
        $request->validate([
            'legal_representative_signature' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        // Eliminar firma anterior si existe
        if ($shop->legal_representative_signature_path) {
            $oldPath = storage_path('app/public/' . $shop->legal_representative_signature_path);
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        // Subir nueva firma
        $file = $request->file('legal_representative_signature');
        $filename = 'legal_signature_shop_' . $shop->id . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('signatures', $filename, 'public');
        
        $shop->legal_representative_signature_path = $path;
        $shop->save();

        return redirect()->route('admin.shop.edit')->with('success', 'Firma del representante legal actualizada correctamente');
    }

    public function deleteSignature(Shop $shop)
    {
        // Validar que el usuario tenga acceso a esta tienda
        if ($shop->id !== Auth::user()->shop->id) {
            abort(403, 'No tienes permisos para modificar esta tienda');
        }

        // Eliminar archivo de firma si existe
        if ($shop->legal_representative_signature_path) {
            $oldPath = storage_path('app/public/' . $shop->legal_representative_signature_path);
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
            
            $shop->legal_representative_signature_path = null;
            $shop->save();
        }

        return redirect()->route('admin.shop.edit')->with('success', 'Firma del representante legal eliminada correctamente');
    }

    public function uploadLogo(Request $request)
    {
        $user = $request->user();
        $shop = $user->shop;

        // Validar la existencia del archivo de imagen
        if ($request->hasFile('logo')) {
            $image = $request->file('logo');
            
            // Eliminar logo anterior si existe
            $file = $shop->logo;
            if($file){
                $existe = Storage::disk('public')->exists($file);
                if($existe){
                    Storage::disk('public')->delete($file);
                }
            }

            // Procesar y optimizar la imagen
            $imageService = new ImageService();
            $imagePath = $imageService->processAndStore($image, 'shop_logos');

            // Actualizar el logo en el modelo shop
            $shop->logo = $imagePath;
            $shop->save();
        }

        return response()->json([
            'ok' => true,
            'shop' => $shop,
        ]);
    }

    public function deleteLogo(Request $request){        
        $user = $request->user();
        $shop = $user->shop;
        // Obtener la ruta de la imagen actual
        $imagePath = $shop->logo;
        // Verificar si hay una imagen almacenada y eliminarla
        if ($imagePath) {
            // Eliminar la imagen del almacenamiento
            Storage::disk('public')->delete($imagePath);
            // Limpiar el atributo de la imagen en el modelo
            $shop->logo = null;
            $shop->save();
        }

        return response()->json([
            'ok' => true,
            'shop' => $shop,
        ]);
    }//.deleteMainImage()

}
