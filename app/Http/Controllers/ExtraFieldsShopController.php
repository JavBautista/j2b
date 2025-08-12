<?php

namespace App\Http\Controllers;

use App\Models\ExtraFieldShop;
use Illuminate\Http\Request;

class ExtraFieldsShopController extends Controller
{
    public function index(){
        $user = auth()->user();
        if ($user->shop) {
            $extra_fields = $user->shop->extraFields;
            return view('admin.configurations.extra_fields_shop.index', ['extra_fields' => $extra_fields]);
        } else {
            return redirect()->route('no_shop_assigned');
        }
    }

    public function create(){
        return view('admin.configurations.extra_fields_shop.create');
    }

    public function edit($id){
        $extraField = ExtraFieldShop::findOrFail($id);
        return view('admin.configurations.extra_fields_shop.edit',['extraField'=>$extraField]);
    }

    public function store(Request $request)
    {
        // Validar los datos del formulario
        $validatedData = $request->validate([
            'field_name' => 'required|string|max:255'
        ]);

         $user = auth()->user();
        if ($user->shop) {
            $shop_id = $user->shop->id;
            // Crear un nuevo campo extra
            $extraField = new ExtraFieldShop();
            $extraField->shop_id = $shop_id;
            $extraField->field_name = $request->field_name;
            $extraField->active = $request->has('active'); // Asignar true si el checkbox está marcado
            $extraField->save();
            // Redireccionar con un mensaje de éxito
            return redirect()->route('admin.configurations.extra_fields')->with('success', '¡Campo extra creado exitosamente!');
        }
        return redirect()->route('admin.configurations.extra_fields')->with('error', '¡No se pudo crear el campo!');

    }

    public function toggleShow($id){
        // Buscar el campo extra por su ID
        $extraField = ExtraFieldShop::findOrFail($id);

        // Cambiar el estado de 'show' del campo extra
        $extraField->active = !$extraField->active;
        $extraField->save();

        // Redireccionar con un mensaje de éxito
        return redirect()->route('admin.configurations.extra_fields')->with('success', '¡Estado del campo extra modificado exitosamente!');
    }

    public function destroy($id){
        // Buscar el campo extra por su ID
        $extraField = ExtraFieldShop::findOrFail($id);

        // Eliminar el campo extra
        $extraField->delete();

        // Redireccionar con un mensaje de éxito
        return redirect()->route('admin.configurations.extra_fields')->with('success', '¡Campo extra eliminado exitosamente!');
    }

    public function update(Request $request, $id){
        // Validar los datos del formulario
        $validatedData = $request->validate([
            'field_name' => 'required|string|max:255',
        ]);

        // Buscar el campo extra a actualizar
        $extraField = ExtraFieldShop::findOrFail($id);
        // Actualizar los valores del campo extra
        $extraField->field_name = $request->field_name;
        $extraField->active = $request->has('active') ? true : false;
        $extraField->save();

        // Redireccionar con un mensaje de éxito
        return redirect()->route('admin.configurations.extra_fields')->with('success', '¡Campo extra actualizado exitosamente!');
    }

    public function getApiExtraFieldsShop(Request $request){
        $user = $request->user();
        $shop = $user->shop;
        $shop_id = $shop->id;
        $extra_fields = ExtraFieldShop::where('shop_id',$shop_id)->get();
        return response()->json([
            'ok'=>true,
            'extra_fields' => $extra_fields
        ]);

    }//getApiExtraFieldsShop()

    public function storeApiExtraFieldsShop(Request $request){
        $user = $request->user();
        $shop = $user->shop;

        $extra_field = new ExtraFieldShop();
        $extra_field->shop_id    = $shop->id;
        $extra_field->field_name = $request->field_name;
        $extra_field->active     = 1;
        $extra_field->save();

        return response()->json([
            'ok'=>true,
            'extra_field' => $extra_field,
        ]);

    }//.storeApiExtraFieldsShop()

    public function updateApiExtraFieldsShop(Request $request){
        $extra_field = ExtraFieldShop::findOrFail($request->id);
        $extra_field->field_name = $request->field_name;
        $extra_field->save();
        return response()->json([
            'ok'=>true,
            'extra_field' => $extra_field,
        ]);

    }//.updateApiExtraFieldsShop()

    public function destroyApiExtraFieldsShop(Request $request){
        $extra_field = ExtraFieldShop::findOrFail($request->id);
        $extra_field->delete();
        return response()->json([
            'ok'=>true,
        ]);

    }//.destroyApiExtraFieldsShop()


}
