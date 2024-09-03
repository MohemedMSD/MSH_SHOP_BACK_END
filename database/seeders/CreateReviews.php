<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use App\Models\Review;
use Intervention\Image\Facades\Image;

class CreateReviews extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //

        $data = [
            [
                'review' => 'This watch is a game-changer for my daily routine.',
                'review_star' => 5,
                'product_id' => 14,
                'user_id' => 4,
                'images' => ['smart_watch_S1.webp'],
            ],
            [
                'review' => 'Good design but the app could be better.',
                'review_star' => 4,
                'product_id' => 16,
                'user_id' => 7,
                'images' => ['smart_watch_X2.webp'],
            ],
            [
                'review' => 'Solid performance with an excellent display.',
                'review_star' => 5,
                'product_id' => 19,
                'user_id' => 10,
                'images' => ['smart_watch_R3.webp', 'smart_watch_Z1.webp'],
            ],
            [
                'review' => 'Some features are not intuitive to use.',
                'review_star' => 3,
                'product_id' => 26,
                'user_id' => 5,
                'images' => ['smart_watch_X3.webp'],
            ],
            [
                'review' => 'Great watch but the battery could last longer.',
                'review_star' => 4,
                'product_id' => 34,
                'user_id' => 9,
                'images' => ['smart_watch_Z2.webp'],
            ],
            [
                'review' => 'Exceptional quality for the price.',
                'review_star' => 5,
                'product_id' => 31,
                'user_id' => 6,
                'images' => ['smart_watch_R2.webp', 'smart_watch_S3.webp'],
            ],
            [
                'review' => 'Nice features but a bit overpriced.',
                'review_star' => 4,
                'product_id' => 21,
                'user_id' => 8,
                'images' => ['smart_watch_Z3.webp'],
            ],
            [
                'review' => 'Love the design and ease of use.',
                'review_star' => 5,
                'product_id' => 28,
                'user_id' => 3,
                'images' => ['smart_watch_X1.webp'],
            ],
            [
                'review' => 'Water-resistant but lacks advanced tracking features.',
                'review_star' => 3,
                'product_id' => 35,
                'user_id' => 4,
                'images' => ['smart_watch_R1.webp'],
            ],
            [
                'review' => 'Perfect for outdoor activities.',
                'review_star' => 5,
                'product_id' => 22,
                'user_id' => 7,
                'images' => ['smart_watch_S2.webp'],
            ],
            [
                'review' => 'Comfortable to wear all day long.',
                'review_star' => 5,
                'product_id' => 25,
                'user_id' => 6,
                'images' => ['smart_watch_R3.webp'],
            ],
            [
                'review' => 'Average smartwatch, nothing too special.',
                'review_star' => 3,
                'product_id' => 17,
                'user_id' => 5,
                'images' => ['smart_watch_Z1.webp'],
            ],
            [
                'review' => 'The screen is a bit small but overall good.',
                'review_star' => 4,
                'product_id' => 30,
                'user_id' => 8,
                'images' => ['smart_watch_X2.webp'],
            ],
            [
                'review' => 'Highly recommend for fitness enthusiasts.',
                'review_star' => 5,
                'product_id' => 24,
                'user_id' => 9,
                'images' => ['smart_watch_Z3.webp'],
            ],
            [
                'review' => 'Great smartwatch with amazing features!',
                'review_star' => 5,
                'product_id' => 14,
                'user_id' => 3,
                'images' => ['smart_watch_R1.webp'],
            ],
            [
                'review' => 'The battery life is fantastic!',
                'review_star' => 4,
                'product_id' => 18,
                'user_id' => 6,
                'images' => ['smart_watch_S2.webp', 'smart_watch_X3.webp'],
            ],
            [
                'review' => 'Very comfortable to wear.',
                'review_star' => 5,
                'product_id' => 21,
                'user_id' => 9,
                'images' => ['smart_watch_Z1.webp'],
            ],
            [
                'review' => 'Good value for the price.',
                'review_star' => 4,
                'product_id' => 15,
                'user_id' => 4,
                'images' => ['smart_watch_S3.webp'],
            ],
            [
                'review' => 'Not bad, but could be better.',
                'review_star' => 3,
                'product_id' => 27,
                'user_id' => 8,
                'images' => ['smart_watch_R2.webp'],
            ],
            [
                'review' => 'Excellent build quality and features.',
                'review_star' => 5,
                'product_id' => 33,
                'user_id' => 7,
                'images' => ['smart_watch_X1.webp', 'smart_watch_S1.webp'],
            ],
            [
                'review' => 'Overall a good smartwatch, but the display is a bit small.',
                'review_star' => 4,
                'product_id' => 23,
                'user_id' => 5,
                'images' => ['smart_watch_R3.webp'],
            ],
            [
                'review' => 'Love the fitness tracking features.',
                'review_star' => 5,
                'product_id' => 36,
                'user_id' => 10,
                'images' => ['smart_watch_Z3.webp'],
            ],
            [
                'review' => 'The watch looks very stylish.',
                'review_star' => 4,
                'product_id' => 17,
                'user_id' => 6,
                'images' => ['smart_watch_S2.webp'],
            ],
            [
                'review' => 'GPS works well, but the heart rate monitor is not accurate.',
                'review_star' => 3,
                'product_id' => 30,
                'user_id' => 8,
                'images' => ['smart_watch_Z2.webp'],
            ],
            [
                'review' => 'Battery lasts longer than expected.',
                'review_star' => 5,
                'product_id' => 20,
                'user_id' => 4,
                'images' => ['smart_watch_X2.webp'],
            ],
            [
                'review' => 'Responsive touch screen and easy to use.',
                'review_star' => 5,
                'product_id' => 29,
                'user_id' => 7,
                'images' => ['smart_watch_R1.webp'],
            ],
            [
                'review' => 'The strap is not very comfortable.',
                'review_star' => 3,
                'product_id' => 24,
                'user_id' => 9,
                'images' => ['smart_watch_S3.webp'],
            ],
            [
                'review' => 'Amazing smartwatch with great features!',
                'review_star' => 5,
                'product_id' => 14,
                'user_id' => 3,
                'images' => [
                    'smart_watch_R1.webp',
                    'smart_watch_R2.webp',
                ],
            ],
            [
                'review' => 'Battery life is decent but could be improved.',
                'review_star' => 4,
                'product_id' => 15,
                'user_id' => 4,
                'images' => [
                    'smart_watch_S1.webp',
                ],
            ],
            [
                'review' => 'The design is sleek, and it fits well on my wrist.',
                'review_star' => 5,
                'product_id' => 16,
                'user_id' => 5,
                'images' => [
                    'smart_watch_X1.webp',
                    'smart_watch_X2.webp',
                ],
            ],
            [
                'review' => 'Great for tracking fitness activities!',
                'review_star' => 4,
                'product_id' => 17,
                'user_id' => 6,
                'images' => [
                    'smart_watch_Z1.webp',
                ],
            ],
            [
                'review' => 'The smartwatch is very durable and perfect for outdoor use.',
                'review_star' => 5,
                'product_id' => 18,
                'user_id' => 7,
                'images' => [
                    'smart_watch_R3.webp',
                    'smart_watch_Z3.webp',
                ],
            ],
            [
                'review' => 'Good value for the price.',
                'review_star' => 4,
                'product_id' => 19,
                'user_id' => 8,
                'images' => [
                    'smart_watch_S3.webp',
                ],
            ],
            [
                'review' => 'Easy to use, and the display is bright.',
                'review_star' => 5,
                'product_id' => 20,
                'user_id' => 9,
                'images' => [
                    'smart_watch_X3.webp',
                    'smart_watch_Z2.webp',
                ],
            ],
            [
                'review' => 'The strap is comfortable, and the watch is lightweight.',
                'review_star' => 4,
                'product_id' => 21,
                'user_id' => 10,
                'images' => [
                    'smart_watch_R2.webp',
                ],
            ],
            [
                'review' => 'I love the customizable watch faces!',
                'review_star' => 5,
                'product_id' => 22,
                'user_id' => 3,
                'images' => [
                    'smart_watch_S2.webp',
                    'smart_watch_R1.webp',
                ],
            ],
            [
                'review' => 'The health monitoring features are very accurate.',
                'review_star' => 5,
                'product_id' => 23,
                'user_id' => 4,
                'images' => [
                    'smart_watch_X2.webp',
                ],
            ],
            [
                'review' => 'Good, but it sometimes loses connection with my phone.',
                'review_star' => 3,
                'product_id' => 24,
                'user_id' => 5,
                'images' => [
                    'smart_watch_Z1.webp',
                    'smart_watch_S3.webp',
                ],
            ],
            [
                'review' => 'Great smartwatch, but the speaker quality could be better.',
                'review_star' => 4,
                'product_id' => 25,
                'user_id' => 6,
                'images' => [
                    'smart_watch_R3.webp',
                ],
            ],
            [
                'review' => 'Love the GPS feature, very accurate.',
                'review_star' => 5,
                'product_id' => 26,
                'user_id' => 7,
                'images' => [
                    'smart_watch_X3.webp',
                    'smart_watch_Z2.webp',
                ],
            ],
            [
                'review' => 'A bit bulky, but overall a great watch.',
                'review_star' => 4,
                'product_id' => 27,
                'user_id' => 8,
                'images' => [
                    'smart_watch_S1.webp',
                ],
            ],
            [
                'review' => 'Very responsive and easy to navigate.',
                'review_star' => 5,
                'product_id' => 28,
                'user_id' => 9,
                'images' => [
                    'smart_watch_R1.webp',
                    'smart_watch_Z3.webp',
                ],
            ],
            [
                'review' => 'The heart rate monitor works perfectly!',
                'review_star' => 5,
                'product_id' => 29,
                'user_id' => 10,
                'images' => [
                    'smart_watch_X1.webp',
                ],
            ],
            [
                'review' => 'Good smartwatch, but the app needs improvement.',
                'review_star' => 3,
                'product_id' => 30,
                'user_id' => 3,
                'images' => [
                    'smart_watch_Z1.webp',
                ],
            ],
            [
                'review' => 'Great watch, fits well and looks stylish.',
                'review_star' => 5,
                'product_id' => 31,
                'user_id' => 4,
                'images' => [
                    'smart_watch_S2.webp',
                    'smart_watch_R2.webp',
                ],
            ],
            [
                'review' => 'The notifications are very helpful and easy to read.',
                'review_star' => 4,
                'product_id' => 32,
                'user_id' => 5,
                'images' => [
                    'smart_watch_X2.webp',
                    'smart_watch_S3.webp',
                ],
            ],
            [
                'review' => 'The display is very clear, even in sunlight.',
                'review_star' => 5,
                'product_id' => 33,
                'user_id' => 6,
                'images' => [
                    'smart_watch_Z2.webp',
                ],
            ],
            [
                'review' => 'I’m impressed by the build quality, very sturdy.',
                'review_star' => 5,
                'product_id' => 34,
                'user_id' => 7,
                'images' => [
                    'smart_watch_R3.webp',
                    'smart_watch_Z3.webp',
                ],
            ],
            [
                'review' => 'Overall, a great smartwatch for the price.',
                'review_star' => 4,
                'product_id' => 35,
                'user_id' => 8,
                'images' => [
                    'smart_watch_X3.webp',
                ],
            ],
            [
                'review' => 'Battery lasts long, and it charges quickly.',
                'review_star' => 5,
                'product_id' => 36,
                'user_id' => 9,
                'images' => [
                    'smart_watch_R1.webp',
                    'smart_watch_S2.webp',
                ],
            ],
        ];
        
        $imagesPath = public_path('uploads');

        $destinationPath = 'public/uploads';

        foreach($data as $review){

            $uploadedImages = [];
                
            $defaultBucket = app('firebase.storage')->getBucket();
                
            foreach ($review['images'] as $key => $imageName) {

                $filePath = $imagesPath . '/' . $imageName;

                if ($defaultBucket->object("reviews/" . $imageName)->exists()) {
                    $newImageName = uniqid() . '_' . $imageName;
                } else {
                    $newImageName = $imageName;
                }

                // Open the image from the static path
                $image = Image::make($filePath);

                // Compress the image with a desired quality (e.g., 60)
                $image->resize(350, 350, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });

                $image->encode('webp', 80);

                $defaultBucket->upload($image->stream()->detach(), [
                    'name' => 'reviews/' . $newImageName,
                ]);

                // Add the compressed image name to the array
                $uploadedImages[] = 'reviews/' . $newImageName;
            }
            
            $review = Review::create([
                'review' => $review['review'],
                'review_star' => $review['review_star'],
                'product_id' => $review['product_id'],
                'user_id' => $review['user_id'],
                'images' => $uploadedImages
            ]);

        }

    }
}
