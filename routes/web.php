<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Route;

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

Route::get('/', function () {
    return view('welcome');
});


/**
 * BUSCA UN POST POR SU ID
 */
Route::get('/find/{id}', function (int $id) {
    return Post::find($id);
});

/**
 * BUSCA UN POS SU ID O RETORNA UN 404
 */
Route::get('/find-or-fail/{id}', function (int $id) {
    // return Post::findOrFail($id);
    try {
        return Post::findOrFail($id);
    } catch (Exception $exception) {
        return $exception->getMessage();
    }
});


/**
 * BUSCA UN POST POR SU ID SELECCIONA COLUMNA O RETORNA UN 404
 */
Route::get('/find-or-fail-with-columns/{id}', function (int $id) {
    return Post::findOrFail($id, ['id', 'title']);
});

/**
 * BUSCA UN POST POR SU SLUG O RETORNA UN 404
 */
Route::get('/find-by-slug/{slug}', function (string $slug) {
    // En lugar de:
    // return Post::where("slug", $slug)->firstorFail();

    //Posdemos hacer esto:
    // return Post::whereSlug($slug)->firstorFail();

    // o mejor aun
    return Post::firstWhere("slug", $slug);
});


/**
 * BUSCA MUCHOS POSTS POR UN ARRAY DE IDS
 */
Route::get('/find-many', function () {
    // En lugar de esto:
    // return Post::whereIn("id", [1,2,3])->get();


    // haz lo siguiente
    return Post::find([1, 2, 3], ['id', 'title']);
});

/**
 * POSTS PAGINADOS CON SELECCIÓN DE COLUMNAS
 */
Route::get('/paginated/{perPage}', function (int $perPage = 10) {

    return Post::paginate($perPage, ['id', 'title']);
});


/**
 * POSTS PAGINADOS MANUALMENTE CON OFFSET/LIMIT
 * offset => desde
 * perPage => hasta
 * http://127.0.0.1:8000/manual-pagination/2 => primera página
 * http://127.0.0.1:8000/manual-pagination/2/2 => segunda página
 */
Route::get('/manual-pagination/{perPage}/{offset?}', function (int $perPage, int $offset = 0) {
    return Post::offset($offset)->limit($perPage)->get();
});

/**
 * CREA UN POST
 */
Route::get('/create', function () {
    $user = User::all()->random(1)->first()->id;
    return Post::create([
        'user_id' => $user,
        'category_id' => Category::all()->random(1)->first()->id,
        'title' => "Post para el usuario $user",
        'content' => "Nuevo post de pruebas",
    ]);
});

/**
 * CREA UN POST O SI EXISTE RETORNALO
 */
Route::get('/first-or-create', function () {
    $user = User::all()->random(1)->first()->id;
    $title = "Post para usuario aleatorio";
    return Post::firstOrCreate(
        ["title" => $title],
        [
            'user_id' => $user,
            'category_id' => Category::all()->random(1)->first()->id,
            'title' => $title,
            'content' => "Nuevo post de pruebas",
        ]
    );
});
