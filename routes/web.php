<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function(){

    $images = Session::get('images');

    return view('welcome', compact('images'));

});

Route::post('upload', function(Request $request){

    $validator = Validator::make($request->all(), [
        'image' => 'mimes:jpg,jpeg,png|max:1024'
    ]);

    if($validator->fails()) {
        return back()->withErrors($validator->errors());
    }


    $extension = $request->file('image')->getClientOriginalExtension();

    $image = Image::make($request->file('image'));

    $height = $image->height();
    $width = $image->width();

    if($width > 500) {
        $resized = Image::make($request->file('image'))->fit(500);
    } else {
        $resized = Image::make($request->file('image'));
    }

    $resized->save('tmp.' . $extension);

    $resizedImg = Image::make('tmp.' . $extension);

    $height = $resizedImg->height();
    $width = $resizedImg->width();

    $puzzlePieceHeight = $height / 3;
    $puzzlePieceWidth = $width / 3;

    $part = 1;

    $images = collect([]);

    for ($y=0; $y <=2 ; $y++) {
        for ($x=0; $x <= 2; $x++) {

            $xOffset = ceil($puzzlePieceWidth * $x);
            $yOffset = ceil($puzzlePieceHeight * $y);

            $partImg = Image::make('tmp.' . $extension)
                            ->crop(
                                ceil($puzzlePieceWidth),
                                ceil($puzzlePieceHeight),
                                $xOffset,
                                $yOffset
                            );

            $partFileName = 'part' . $part . '.' . $extension;

            $partImg->save($partFileName);

            $images->add([ 'image_url' => $partFileName,  'part_no' => $part++ ]);

        }
    }

    File::delete('tmp.' . $extension);


    return redirect('/')->with('images', $images->shuffle());

})->name('upload');
