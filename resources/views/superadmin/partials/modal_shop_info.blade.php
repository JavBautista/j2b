{{-- Modal Ver Datos Tienda (igual que en shops) --}}
<div class="modal fade j2b-modal" id="shopInfoModal{{ $shop->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-shopping-cart" style="color: var(--j2b-primary);"></i> Ver Datos
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">

                <p class="mb-3"><small style="color: var(--j2b-danger);">* Campos obligatorios</small></p>

                {{-- Informacion Basica --}}
                <div class="j2b-form-section">
                    <h6 class="j2b-form-section-title">
                        <i class="fa fa-info-circle"></i> Informacion Basica
                    </h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="j2b-form-group">
                                <label class="j2b-label"><span style="color: var(--j2b-danger);">*</span> Nombre de la Tienda</label>
                                <input type="text" class="j2b-input" value="{{ $shop->name }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="j2b-form-group">
                                <label class="j2b-label">Propietario</label>
                                <input type="text" class="j2b-input" value="{{ $shop->owner_name }}" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="j2b-form-group">
                        <label class="j2b-label">Descripcion</label>
                        <textarea class="j2b-input" rows="2" readonly>{{ $shop->description }}</textarea>
                    </div>
                </div>

                {{-- Direccion y Contacto --}}
                <div class="j2b-form-section">
                    <h6 class="j2b-form-section-title">
                        <i class="fa fa-map-marker"></i> Direccion y Contacto
                    </h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="j2b-form-group">
                                <label class="j2b-label">Calle</label>
                                <input type="text" class="j2b-input" value="{{ $shop->address }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="j2b-form-group">
                                <label class="j2b-label">Num. Ext.</label>
                                <input type="text" class="j2b-input" value="{{ $shop->number_out }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="j2b-form-group">
                                <label class="j2b-label">Num. Int.</label>
                                <input type="text" class="j2b-input" value="{{ $shop->number_int }}" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="j2b-form-group">
                                <label class="j2b-label">Colonia</label>
                                <input type="text" class="j2b-input" value="{{ $shop->district }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="j2b-form-group">
                                <label class="j2b-label">CP</label>
                                <input type="text" class="j2b-input" value="{{ $shop->zip_code }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="j2b-form-group">
                                <label class="j2b-label">Ciudad</label>
                                <input type="text" class="j2b-input" value="{{ $shop->city }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="j2b-form-group">
                                <label class="j2b-label">Estado</label>
                                <input type="text" class="j2b-input" value="{{ $shop->state }}" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-5">
                            <div class="j2b-form-group">
                                <label class="j2b-label">Telefono/Celular</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="text" class="j2b-input" value="{{ $shop->phone }}" readonly>
                                    <label class="d-flex align-items-center gap-1" style="white-space: nowrap;">
                                        <input type="checkbox" {{ $shop->whatsapp ? 'checked' : '' }} disabled>
                                        <i class="fa fa-whatsapp" style="color: #25D366;"></i>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="j2b-form-group">
                                <label class="j2b-label">Email</label>
                                <input type="email" class="j2b-input" value="{{ $shop->email }}" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Info Empresarial --}}
                <div class="j2b-form-section">
                    <h6 class="j2b-form-section-title">
                        <i class="fa fa-building"></i> Informacion Empresarial
                    </h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="j2b-form-group">
                                <label class="j2b-label">Slogan</label>
                                <input type="text" class="j2b-input" value="{{ $shop->slogan }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="j2b-form-group">
                                <label class="j2b-label">Presentacion</label>
                                <input type="text" class="j2b-input" value="{{ $shop->presentation }}" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="j2b-form-group">
                                <label class="j2b-label">Mision</label>
                                <input type="text" class="j2b-input" value="{{ $shop->mission }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="j2b-form-group">
                                <label class="j2b-label">Vision</label>
                                <input type="text" class="j2b-input" value="{{ $shop->vision }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="j2b-form-group">
                                <label class="j2b-label">Valores</label>
                                <input type="text" class="j2b-input" value="{{ $shop->values }}" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Datos Bancarios --}}
                <div class="j2b-form-section">
                    <h6 class="j2b-form-section-title">
                        <i class="fa fa-bank"></i> Datos Bancarios
                    </h6>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="j2b-form-group">
                                <label class="j2b-label">Banco</label>
                                <input type="text" class="j2b-input" value="{{ $shop->bank_name }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="j2b-form-group">
                                <label class="j2b-label">Cuenta Principal</label>
                                <input type="text" class="j2b-input" value="{{ $shop->bank_number }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="j2b-form-group">
                                <label class="j2b-label">Cuenta Secundaria</label>
                                <input type="text" class="j2b-input" value="{{ $shop->bank_number_secondary }}" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Redes Sociales --}}
                <div class="j2b-form-section">
                    <h6 class="j2b-form-section-title">
                        <i class="fa fa-share-alt"></i> Redes Sociales
                    </h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="j2b-form-group">
                                <label class="j2b-label"><i class="fa fa-globe"></i> Pagina web</label>
                                <input type="text" class="j2b-input" value="{{ $shop->web }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="j2b-form-group">
                                <label class="j2b-label"><i class="fa fa-facebook"></i> Facebook</label>
                                <input type="text" class="j2b-input" value="{{ $shop->facebook }}" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="j2b-form-group">
                                <label class="j2b-label"><i class="fa fa-twitter"></i> Twitter</label>
                                <input type="text" class="j2b-input" value="{{ $shop->twitter }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="j2b-form-group">
                                <label class="j2b-label"><i class="fa fa-instagram"></i> Instagram</label>
                                <input type="text" class="j2b-input" value="{{ $shop->instagram }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="j2b-form-group">
                                <label class="j2b-label"><i class="fa fa-pinterest"></i> Pinterest</label>
                                <input type="text" class="j2b-input" value="{{ $shop->pinterest }}" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="j2b-form-group">
                        <label class="j2b-label"><i class="fa fa-youtube-play"></i> Canal de video</label>
                        <input type="text" class="j2b-input" value="{{ $shop->video_channel }}" readonly>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="j2b-btn j2b-btn-secondary" data-bs-dismiss="modal">
                    <i class="fa fa-times"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
