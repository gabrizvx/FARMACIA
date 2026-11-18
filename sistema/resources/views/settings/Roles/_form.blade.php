 <div class="mb-3">
                    <label for="name" class="form-label">Nombre de la categoria</label>
                    <input type="text" name="name" id="name"
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name', $category->name ?? '')}}">
                </div>
                @error('name')
                 <div class="invalid-feedback"{{ $message}}></div>   
                @enderror
                <div class="mb-3">
                    <label for="description" class="form-label">Descripcion</label>
                    <textarea name="description" id="description" rows="3"
                            class="form-control @error('description') is-invalid @enderror">{{ old('description', $category->name ?? '')}}</textarea>
                            
                </div>
                 @error('description')
                 <div class="invalid-feedback"{{ $message}}></div>   
                @enderror
                <button type="submit" class="btn btn-primary text-white">Guardar</button>
                <a href="" class="btn btn-secondary">Cancelar</a>

    