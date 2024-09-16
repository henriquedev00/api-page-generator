<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request): ProductCollection
    {
        try {
            $products =  Product::query()
                ->when($request->query('slug'), function ($query) use ($request) {
                    $query->where('slug', 'LIKE', "%{$request->query('slug')}%");
                })
                ->with(['creator', 'updater'])
                ->get();

            return new ProductCollection($products);
        } catch(Throwable $th) {
            report($th);

            return response()->noContent(500);
        }
    }

    public function show(Product $product): ProductResource
    {
        try {
            return new ProductResource($product);
        } catch(Throwable $th) {
            report($th);

            return response()->noContent(500);
        }
    }

    public function store(Request $request): Response
    {
        try {            
            $user = Auth::user();

            $data = [
                'header' => $request->input('header'),
                'details' => $request->input('details'),
                'footer' => $request->input('footer'),
                'images' => [
                    'header' => ['logo' => $request->file('header.logo')],
                    'details' => $request->file('details.images', []),
                    'footer' => ['logo' => $request->file('footer.logo')],
                ]
            ];

            if (isset($data['details']['slug'])) {
                $data['details']['slug'] = Str::slug($data['details']['slug']);
            }

            DB::beginTransaction();

            $product = Product::create([
                'name' => $data['details']['name'],
                'slug' => $data['details']['slug'],
                'header' => json_encode($data['header'], JSON_UNESCAPED_SLASHES),
                'details' => json_encode($data['details'], JSON_UNESCAPED_SLASHES),
                'footer' => json_encode($data['footer'], JSON_UNESCAPED_SLASHES),
                'images' => '',
                'created_by' => $user->id,
                'updated_by' => $user->id
            ]);

            foreach($data['images'] as $section => $images) {
                foreach($images as $field => $image) {
                    if ($image instanceof UploadedFile && $image->isValid()) {
                        $imagePath = 'products/' . Str::uuid()->toString() . '.webp';

                        $image->storeAs($imagePath);

                        $data['images'][$section][$field] = $imagePath;
                    } else {
                        unset($data['images'][$section][$field]);
                    }
                }
            }

            $product->images = json_encode($data['images'], JSON_UNESCAPED_SLASHES);

            $product->save();

            DB::commit();

            return response()->noContent(201);
        } catch(Throwable $th) {
            DB::rollBack();

            report($th);

            return response()->noContent(500);
        }
    }

    public function update(Product $product, Request $request): Response
    {
        try {
            $user = Auth::user();

            $data = [
                'header' => $request->input('header'),
                'details' => $request->input('details'),
                'footer' => $request->input('footer'),
                'images' => [
                    'header' => ['logo' => $request->file('header.logo')],
                    'details' => $request->file('details.images', []),
                    'footer' => ['logo' => $request->file('footer.logo')],
                ]
            ];

            if (isset($data['details']['slug'])) {
                $data['details']['slug'] = Str::slug($data['details']['slug']);
            }

            DB::beginTransaction();

            $product->update([
                'name' => $data['details']['name'],
                'slug' => $data['details']['slug'],
                'header' => json_encode($data['header'], JSON_UNESCAPED_SLASHES),
                'details' => json_encode($data['details'], JSON_UNESCAPED_SLASHES),
                'footer' => json_encode($data['footer'], JSON_UNESCAPED_SLASHES),
                'updated_by' => $user->id
            ]);

            $imagesOld = json_decode($product->images, true);
            $imagesToDelete = [];

            foreach($data['images'] as $section => $fields) {
                foreach($fields as $field => $image) {
                    if ($image instanceof UploadedFile && $image->isValid()) {
                        $imagePath = 'products/' . Str::uuid()->toString() . '.webp';

                        $image->storeAs($imagePath);

                        $data['images'][$section][$field] = $imagePath;

                        if (isset($imagesOld[$section][$field])) {
                            $imagesToDelete[] = $imagesOld[$section][$field];
                        }
                    } else {
                        $data['images'][$section][$field] = $imagesOld[$section][$field];
                    }
                }
            }

            if ($data['images']['details'] == []) {
                $data['images']['details'] = $imagesOld['details'];
            }

            $product->images = json_encode($data['images'], JSON_UNESCAPED_SLASHES);

            $product->save();

            DB::commit();

            foreach($imagesToDelete as $image) {
                Storage::delete($image);
            }

            return response()->noContent();
        } catch(Throwable $th) {
            DB::rollBack();

            report($th);

            return response()->noContent(500);
        }
    }

    public function delete(Product $product): Response
    {
        try {
            DB::beginTransaction();

            $product->delete();

            $images = json_decode($product->images, true);

            foreach($images as $fields) {
                foreach($fields as $image) {
                    Storage::delete($image);
                }
            }

            DB::commit();

            return response()->noContent();
        } catch(Throwable $th) {
            DB::rollBack();

            report($th);

            return response()->noContent(500);
        }
    }
}
