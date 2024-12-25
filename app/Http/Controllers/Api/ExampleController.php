<?php

namespace App\Http\Controllers\Api;

// Log Info error
use Illuminate\Support\Facades\Log; 

// Import model Example
use App\Models\Example;

use App\Http\Controllers\Controller;

// Import resource GeneralResource
use App\Http\Resources\GeneralResource;

// Import Http request
use Illuminate\Http\Request;

// Import facade Validator
use Illuminate\Support\Facades\Validator;

// Import facade Storage
use Illuminate\Support\Facades\Storage;

class ExampleController extends Controller
{
    public function index()
    {
        // Get all examples
        $examples = Example::latest()->paginate(5);
        
        // Return collection of examples as a resource
        return new GeneralResource(true, '26 Desember 2024', $examples, 200);
    }
    

    public function store(Request $request)
    {
        // Define validation rules
        $validator = Validator::make($request->all(), [
            'image'     => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'title'     => 'required',
            'content'   => 'required',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return new GeneralResource(false, 'Validation Error', $validator->errors(), 422);
        }

        // Upload image
        $image = $request->file('image');
        $image->storeAs('public/examples', $image->hashName());

        // Create example
        $example = Example::create([
            'image'     => $image->hashName(),
            'title'     => $request->title,
            'content'   => $request->content,
        ]);

        // Return response
        return new GeneralResource(true, 'Data Example Berhasil Ditambahkan!', $example, 201);
    }

    public function show($id)
    {
        // Find example by ID
        $example = Example::find($id);

        // Check if example exists
        if (!$example) {
            return new GeneralResource(false, 'Data Example Tidak Ditemukan!', null, 404);
        }

        // Return single example as a resource
        return new GeneralResource(true, 'Detail Data Example!', $example, 200);
    }

    public function update(Request $request, $id)
    {
        // Define validation rules
        $validator = Validator::make($request->all(), [
            'title'     => 'required',
            'content'   => 'required',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return new GeneralResource(false, 'Validation Error', $validator->errors(), 422);
        }

        // Find example by ID
        $example = Example::find($id);

        // Check if example exists
        if (!$example) {
            return new GeneralResource(false, 'Data Example Tidak Ditemukan!', null, 404);
        }

        // Check if image is not empty
        if ($request->hasFile('image')) {

            // Upload image
            $image = $request->file('image');
            $image->storeAs('public/examples', $image->hashName());

            // Delete old image
            Storage::delete('public/examples/' . basename($example->image));

            // Update example with new image
            $example->update([
                'image'     => $image->hashName(),
                'title'     => $request->title,
                'content'   => $request->content,
            ]);
        } else {
            // Update example without image
            $example->update([
                'title'     => $request->title,
                'content'   => $request->content,
            ]);
        }

        // Return response
        return new GeneralResource(true, 'Data Example Berhasil Diubah!', $example, 200);
    }

    public function destroy($id)
    {
        // Find example by ID
        $example = Example::find($id);

        // Check if example exists
        if (!$example) {
            return new GeneralResource(false, 'Data Example Tidak Ditemukan!', null, 404);
        }

        // Delete image
        Storage::delete('public/examples/' . basename($example->image));

        // Delete example
        $example->delete();

        // Return response
        return new GeneralResource(true, 'Data Example Berhasil Dihapus!', null, 200);
    }
}
