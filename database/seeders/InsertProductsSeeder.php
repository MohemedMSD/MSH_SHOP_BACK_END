<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use App\Models\Product;
use Intervention\Image\Facades\Image;

class InsertProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run() : void
    {

        $products = [
            [
                'name' => 'Earphone P123',
                'description' => 'High-quality earphone with crystal-clear sound and ergonomic design.',
                'price' => 42,
                'purchase_price' => 26,
                'quantity' => 90,
                'category_id' => 4,
                'images' => [
                    'earphones_a_1.webp',
                    'earphones_a_2.webp',
                    'earphones_a_3.webp',
                    'earphones_a_4.webp',
                ],
                'discount' => 1,
            ],
            [
                'name' => 'Earphone 5S Plus',
                'description' => 'Enhanced earphone with deep bass and noise cancellation.',
                'price' => 79,
                'purchase_price' => 50,
                'quantity' => 75,
                'category_id' => 4,
                'images' => [
                    'earphones_b_1.webp',
                    'earphones_b_2.webp',
                    'earphones_b_4.webp',
                ],
                'discount' => 2,  // Assuming a discount ID
            ],
            [
                'name' => 'Earphone XSS Pro',
                'description' => 'Premium earphone offering superior sound quality and long-lasting comfort.',
                'price' => 35,
                'purchase_price' => 20,
                'quantity' => 60,
                'category_id' => 4,
                'images' => [
                    'earphones_c_1.webp',
                    'earphones_c_2.webp',
                    'earphones_c_3.webp',
                    'earphones_c_4.webp',
                ],
                'discount' => 1,
            ],
            [
                'name' => 'Headphone IEX4',
                'description' => 'High-fidelity headphone with immersive sound and noise isolation.',
                'price' => 130,
                'purchase_price' => 80,
                'quantity' => 40,
                'category_id' => 3,
                'images' => [
                    'headphones_a_1.webp',
                    'headphones_a_2.webp',
                    'headphones_a_3.webp',
                    'headphones_a_4.webp',
                ],
                'discount' => null,  // Assuming a discount ID
            ],
            [
                'name' => 'Headphone OX5S Plus',
                'description' => 'Advanced headphone with superior bass and wireless connectivity.',
                'price' => 140,
                'purchase_price' => 84,
                'quantity' => 50,
                'category_id' => 3,
                'images' => [
                    'headphones_b_1.webp',
                    'headphones_b_2.webp',
                    'headphones_b_3.webp',
                    'headphones_b_4.webp',
                ],
                'discount' => null,
            ],
            [
                'name' => 'Headphone Magic Pro',
                'description' => 'Professional-grade headphone with studio-quality sound.',
                'price' => 150,
                'purchase_price' => 90,
                'quantity' => 30,
                'category_id' => 3,
                'images' => [
                    'headphones_c_1.webp',
                    'headphones_c_2.webp',
                    'headphones_c_3.webp',
                    'headphones_c_4.webp',
                ],
                'discount' => 2,  // Assuming a discount ID
            ],
            [
                'name' => 'Smart Watch S50',
                'description' => 'A premium smartwatch with advanced fitness tracking features.',
                'price' => 250,
                'purchase_price' => 160,
                'quantity' => 50,
                'category_id' => 9, // Assuming category ID 1
                'images' => [
                    'smart_watch_R1.webp',
                    'smart_watch_R2.webp',
                    'smart_watch_R3.webp',
                ],
                'discount' => null,
            ],
            [
                'name' => 'Smart Watch 98D',
                'description' => 'A sleek smartwatch with a long-lasting battery and modern design.',
                'price' => 190,
                'purchase_price' => 100,
                'quantity' => 75,
                'category_id' => 9, // Assuming category ID 2
                'images' => [
                    'smart_watch_S1.webp',
                    'smart_watch_S2.webp',
                    'smart_watch_S3.webp',
                ],
                'discount' => null, // Assuming a 10% discount
            ],
            [
                'name' => 'Smart Watch AB12',
                'description' => 'An innovative smartwatch with customizable watch faces and health monitoring.',
                'price' => 350,
                'purchase_price' => 250,
                'quantity' => 40,
                'category_id' => 9, // Assuming category ID 3
                'images' => [
                    'smart_watch_X1.webp',
                    'smart_watch_X2.webp',
                    'smart_watch_X3.webp',
                ],
                'discount' => null,
            ],
            [
                'name' => 'Smart Watch Z1 SX',
                'description' => 'A rugged smartwatch built for outdoor adventures and extreme conditions.',
                'price' => 400,
                'purchase_price' => 300,
                'quantity' => 30,
                'category_id' => 9, // Assuming category ID 4
                'images' => [
                    'smart_watch_Z1.webp',
                    'smart_watch_Z2.webp',
                    'smart_watch_Z3.webp',
                ],
                'discount' => null, // Assuming a 15% discount
            ],
            [
                'name' => 'Speaker AI MAX',
                'description' => 'Powerful speaker with rich bass and crystal-clear sound.',
                'price' => 100,
                'purchase_price' => 60,
                'quantity' => 80,
                'category_id' => 6,
                'images' => [
                    'speaker1.webp',
                    'speaker2.webp',
                    'speaker3.webp',
                    'speaker4.webp',
                ],
                'discount' => 1,
            ],
            [
                'name' => 'Smart Watch DRAGON',
                'description' => 'Innovative smartwatch with fitness tracking and customizable watch faces.',
                'price' => 400,
                'purchase_price' => 250,
                'quantity' => 50,
                'category_id' => 9,
                'images' => [
                    'watch_1.webp',
                    'watch_2.webp',
                    'watch_3.webp',
                    'watch_4.webp',
                ],
                'discount' => 1,  // Assuming a discount ID
            ],
        ];
        
        $imagesPath = public_path('uploads');

        $destinationPath = 'public/uploads';

        foreach($products as $product){

            $uploadedImages = [];

            $defaultBucket = app('firebase.storage')->getBucket();
                
            foreach ($product['images'] as $key => $imageName) {

                $filePath = $imagesPath . '/' . $imageName;

                if ($defaultBucket->object("products/" . $imageName)->exists()) {
                    $newImageName = uniqid() . '_' . $imageName;
                } else {
                    $newImageName = $imageName;
                }

                // Open the image from the static path
                $image = Image::make($filePath);

                // Compress the image with a desired quality (e.g., 60)
                $image->resize(450, 450, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });

                $image->encode('webp', 80);

                $defaultBucket->upload($image->stream()->detach(), [
                    'name' => 'products/' . $newImageName,
                ]);

                // Add the compressed image name to the array
                $uploadedImages[] = 'products/' . $newImageName;
            }

            // Create the product with the static data
            $product = Product::create([
                'name' => $product['name'],
                'description' => $product['description'],
                'price' => $product['price'],
                'purchase_price' => $product['purchase_price'],
                'quantity' => $product['quantity'],
                'category_id' => $product['category_id'],  // Replace with a valid category ID
                'images' => $uploadedImages,
                'discount_id' => $product['discount']  // Replace with a valid discount ID if necessary
            ]);

        }
    


    }
}
