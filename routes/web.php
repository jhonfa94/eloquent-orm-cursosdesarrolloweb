<?php

use App\Models\Post;
use App\Models\User;
use App\Models\Billing;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
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

/* =====================
  ! TRABAJO CON RELACIONES Y VARIOS
========================= */

/**
 * BUSCA UN POST Y CARGA SU AUTOR Y TAGS CON TODA LA INFORMACIÓN
 */
Route::get('/with-relations/{id}', function (int $id) {
    return Post::with("user", "category", "tags")->find($id);
});

/**
 * BUSCA UN POST Y CARGA SU AUTOR Y TAGS CON TODA LA INFORMACIÓN UTILICANZO LOAD
 */
Route::get('/with-relations-using-load/{id}', function (int $id) {
    // return Post::with("user","category", "tags")->find($id);
    $post = Post::findOrFail($id);
    $post->load("user", "category", "tags");
    return $post;
});

/**
 * BUSCA UN POST Y CARGA SU AUTOR Y TAGS CON SELECCION DE COLUMNAS EN RELACIONES
 */
Route::get('/with-relations-and-columns/{id}', function (int $id) {
    return Post::select(['id', 'user_id', "category_id", 'title'])
        ->with([
            "user:id,name,email",
            "user.billing:id,user_id,credit_card_number",
            "tags:id,tag",
            "category:id,name",
        ])
        ->find($id);
});

/**
 * BUSCA UN USUARIO Y CARGA EL NÚMERO DE POSTS QUE TIENE
 */
Route::get('/with-count-post/{id}', function (int $id) {
    return User::select(['id', 'name', 'email'])
        ->withCount('post')
        ->findOrFail($id);
});

/**
 * BUSCA UN POST O RETORNA UN 404, PERO SI EXISTE ACTUALÍZALO
 */
Route::get('/update/{id}', function (int $id) {
    // En lugar de hacer los siguiente:
    // $post = Post::findOrFail($id);
    // $post->title = "Post actualizado";
    // $post->save();
    // return $post;

    // Haz lo siguiente:
    return Post::findOrFail($id)->update([
        'title' => "Post actualziado de nuevo"
    ]);
});

/**
 * ACTUALIZA UNPOST EXISTENTE OPR SU SLUG O LO CREA SI NO EXISTE
 */
Route::get("/update-or-create/{slug}", function (string $slug) {
    $user = User::all()->random(1)->first()->id;
    return Post::updateOrCreate(
        ["slug" => $slug],
        [
            'user_id' => $user,
            'category_id' => Category::all()->random(1)->first()->id,
            'title' => "Nuevo contenido del post",
            'content' => "Nuevo contenido del post actualizado",
        ]
    );
});

/**
 * ACTUALIZA UNPOST EXISTENTE OPR SU SLUG O LO CREA SI NO EXISTE
 */
Route::get("/delete-with-tags/{id}", function (int $id) {
    try {
        DB::beginTransaction();
        $post = Post::findOrFail($id);
        $post->tags()->detach();
        $post->delete();

        DB::commit();
        return $post;
    } catch (Exception $exception) {
        DB::rollBack();
        return $exception->getMessage();
    }
});


/**
 * BUSCA UN POST O RETORNA UN 404, PERO SI EXISTE DALE LIKE
 */
Route::get("/likes/{id}", function (int $id) {
    // en lugar de:
    // $post = Post::findOrFail($id);
    // $post->likes++;
    // $post->save();

    // haz lo siguiente:
    return Post::findOrFail($id)->increment('likes', 2, [
        "title" => "Post con muchos likes",
    ]);
});

/**
 * BUSCA UN POST O RETORNA UN 404, PERO SI EXISTE DALE LIKE
 */
Route::get("/dislike/{id}", function (int $id) {
    // en lugar de:
    // $post = Post::findOrFail($id);
    // $post->dislikes++;
    // $post->save();

    // haz lo siguiente:
    //    tenemos el método de decrement para decrementar el valor
    return Post::findOrFail($id)->increment('dislikes', 1, [
        "title" => "Post con muchos likes",
    ]);
});

/**
 * PROCESOS COMPLEJOS BASADOS EN CHUNKS
 */
Route::get("/chunks/{amount}", function (int $amount) {
    // Podemos utilizar la fuccion de sleep para no sobrecargar el server
    Post::chunk($amount, function (Collection $chunk) {
        dd($chunk);
        //         sleep(5);
    });
});

/**
 * CREA UN USUARIO Y SU INFORMACIÓN DE PAGO
 * SI EXISTE EL USUARIO LO UTILIZA
 * SI EXISTE ELMÉTODO DE PAGOLO ACTUALIZA
 */
Route::get("/create-with-relation", function () {
    try {
        DB::beginTransaction();
        // Buscamos o creamos el usuario
        $user = User::firstOrCreate(
            ["name" => "cursosdesarrolloweb"],
            [
                "name" => "cursosdesarrollosweb",
                "email" => "eloquent@cursosdesarrollosweb.es",
                "password" => bcrypt("password"),
                "age" => 25
            ]
        );
        // Buscamos o creamos el metodo de pago y lo asociamos al usuario
        // $user->billing()->updatedOrCreate(
        Billing::updatedOrCreate(
            ["user_id" => $user->id],
            [
                "user_id" => $user->id,
                "credit_card_number" => "1235656"
            ]
        );
        DB::commit();
        return $user
            ->load("billing:id,user_id,credit_card_number");
    } catch (Exception $exception) {
        DB::rollBack();
        return $exception->getMessage();
    }
});


/**
 * ACTUALIZA UN POST Y SUS RELACIONES
 */
Route::get("/update-with-relation/{id}", function (int $id) {
    $post = Post::findOrFail($id);
    $post->title = "Post actualizado con relaciones a este id";
    $post->tags()->attach(Tag::all()->random(1)->first()->id);
    $post->save();
});

/**
 * POST QUE TENGA MÁS DE 2 TAGS RELACIONADOS
 */
Route::get("/has-two-or-more", function () {
    return Post::select(["id", "title"])
        ->withCount("tags")
        ->has("tags", ">=", "4")
        ->get();
});


/**
 * BUSCAR UN POST Y CARGA SUS TAGS ORDENADOS POR NOMBRE ASCENDENTEMENTE
 *
 * añadir relación sortedTags a modelo Post
 */
Route::get("/with-tags-sorted/{id}", function (int $id) {
    return Post::with("sortedTags:id,tag")
        ->find($id);
});


/**
 * BUSCA TODOS LOS POST QUE TENGAN TAGS
 */
Route::get("/with-where-has-tags", function () {
    return Post::select(['id', 'title'])
        ->with("tags:id,tag")
        ->whereHas("tags")
        // ->whereDoesntHave("tags")
        // ->orderby('id')
        ->get();
});



/**
 * SCOPE PARA BUSCAR TODOS LOS POST QUE TENGAN TAGS
 *
 * añadir scopeWhereHasTagsWithTags a modelo Post
 */
Route::get("/scope-with-where-has-tags", function () {
    return Post::whereHasTagsWithTags()->get();
});

/**
 * BUSCA UN POST Y CARGA SU AUTOR DE FORMA AUTOMÁTICA Y US TAGS CON TODA LA INFORMACIÓN
 *
 * añadir protected $with = ["user:id,name,email",]; a modelo Post
 */
Route::get("/autoload-user-from-post-with-tags/{id}", function (int $id) {
    return Post::with("tags:id,tag")->findOrFail($id);
});


/**
 * POST CON ATRIBUTOS PERSONALIZADOS
 *
 * añadir getTitleWithAuthorAttribute a modelo Post
 */
Route::get("/custom-attributes/{id}", function (int $id) {
    return Post::with("user:id,name")->findOrFail($id);
});


/**
 * BUSCA POST POR FECHA DE ALTA, VÁLIDO FORMATO Y-m-d
 * ejemplo: url/by-created/2022-02-19
 */
Route::get("/by-created/{date}", function (string $date) {

    return Post::whereDate('created_at', $date)->get();
});

/**
 * BUSCA POST POR DÍA Y MES EN FECHA DE ALTA
 * ejemplo: url/by-created-at_month-day/2022-02-19
 */
Route::get("/by-created-at_month-day/{day}/{month}", function (int $day, int $month) {

    return Post::whereMonth('created_at', $month)
        ->whereDay('created_at', $day)
        ->get();
});

/**
 * BUSCA POST POR UN RANGO DE FECHAS DE ALTA
 * ejemplo: http://127.0.0.1:8000/by-created-at/2022-01-28/2022-02-28
 */
Route::get("/by-created-at/{start}/{end}", function (string $start, string $end) {

    return Post::whereBetween('created_at', [$start, $end])->get();
});


/**
 * OBTIENE TODOS LOS POSTS QUE EL DÍA DEL MES SEA SUPERIOR A 5 O UNO POR SLUG SI EXISTE LA QUERYSTRING SLUG
 * EJEMPLO: http://127.0.0.1/when-slug?slug=<slug>
 */
Route::get("/when-slug", function () {
    return Post::whereMonth('created_at', now()->month)
        ->whereDay('created_at', '>=', 5)
        ->when(request()->query("slug"), function (Builder $builder) {
            $builder->whereSlug(request()->query("slug"));
        })
        ->get();
});


/**
 * SUBQUERIES PARA COSULTAS AVANZADAS
 */
Route::get("/subquery", function () {
    return User::where(function (Builder $builder) {
        $builder->where("banned", true)
            ->where("age", ">=", 50);
    })
        ->orWhere(function (Builder $builder) {
            $builder->where("banned", false)
                ->where("age", "<=", 50);
        })
        ->get();
});

/**
 * SCOPE GLOBAL EN POSTS PARA OBTENER SÓLO POSTS DE ESTE MES
 *
 */
Route::get("/global-scope-posts-current-month", function () {
    return Post::count();
});

/**
 * DESHABILITAR SCOPE GLOBAL EN POSTS PARA OBTENER TODOS LOS POSTS
 */
Route::get("/without-global-scope-posts-current-month", function () {
    return Post::withoutGlobalScope("currentMonth")->count();
});

/**
 * POST AGRUPADOS POR CATEGORÍA CON SUMA DE LIKS Y DISLIKES
 */
Route::get("/query-raw", function () {
    return Post::withoutGlobalScope("currentMonth")
        ->with("category")
        ->select([
            "id",
            "category_id",
            "likes",
            "dislikes",
            DB::raw("SUM(likes) as total_likes"),
            DB::raw("SUM(dislikes) as total_dislikes"),
        ])
        ->groupBy("category_id")
        ->get();
});


/**
 * POST AGRUPADOS POR CATEGORÍA CON SUMA DE LIKS Y DISLIKES QUE SUMEN MÁS DE 100 LIKES
 */
Route::get("/query-raw-having-raw", function () {
    return Post::withoutGlobalScope("currentMonth")
        ->with("category")
        ->select([
            "id",
            "category_id",
            "likes",
            "dislikes",
            DB::raw("SUM(likes) as total_likes"),
            DB::raw("SUM(dislikes) as total_dislikes"),
        ])
        ->groupBy("category_id")
        ->havingRaw("SUM(likes) >= ?", [110])
        ->get();
});


/**
 * USUARIOS ORDENADOS POR SU ÚLTIMO POST
 */
Route::get("/order-by-subqueries", function () {
    return User::select(["id", "name"])
        ->has("posts")
        ->orderByDesc(
            Post::withoutGlobalScope("currentMonth")
                ->select("created_at")
                ->whereColumn("user_id", "users.id")
                ->orderBy("created_at", "desc")
                ->limit(1)
        )
        ->get();
});

/**
 * USUARIOS QUE TIENE POSTS CON SU ÚLTIMO POST PUBLICADO
 */
Route::get("/select-subqueries", function () {
    return User::select(["id", "name"])
        ->has("posts")
        ->addSelect([
            "last_post" => Post::withoutGlobalScope("currentMonth")
                ->select('title')
                ->whereColumn("user_id", "users.id")
                ->orderBy("created_at", "desc")
                ->limit(1)
        ])
        ->get();
});


/**
 * INSERT MASIVO DE USUARIOS
 */
Route::get("/multiple-insert", function () {
    $users = new Collection;
    for ($i = 1; $i <= 20; $i++) {
        $users->push([
            "name" => "usuario $i",
            "email" => "usuario$i@m.com",
            "password" => bcrypt("password"),
            "email_verified_at" => now(),
            "created_at" => now(),
            "age" => rand(20, 50)
        ]);
    }
    User::insert($users->toArray());
});

/**
 * INSERT BATCH
 */
Route::get("/batch-insert", function () {
    $userInstance = new User;
    $columns = [
        'name',
        'email',
        'password',
        'age',
        'banned',
        'email_verified_at',
        'created_at'
    ];
    $users = new Collection;
    for ($i = 1; $i <= 150; $i++) {
        $users->push([
            "usuario $i",
            "usuario$i@m.com",
            bcrypt("password"),
            rand(20, 50),
            rand(0, 1),
            now(),
            now(),
        ]);
    }
    $batchSize = 100; // insert 500 (default), 100 minimum rows in one query

    /** @var Mavinoo\Batch\Batch $batch */
    $batch = batch();
    return $batch->insert($userInstance, $columns, $users->toArray(), $batchSize);
});


/**
 * UPDATE BATCH
 */
Route::get("/batch-update", function () {
    $postInstance = new Post;

    $toUpdate = [
        [
            "id" => 1,
            "likes" => ["*", 2], // multiplica
            "dislikes" => ["/", 2], // divide
        ],
        [
            "id" => 2,
            "likes" => ["-", 2], // resta
            "title" => "Nuevo título",
        ],
        [
            "id" => 3,
            "likes" => ["+", 5], // suma
        ],
        [
            "id" => 4,
            "likes" => ["*", 2], // multiplica
        ],
    ];

    $index = "id";

    /** @var Mavinoo\Batch\Batch $batch */
    $batch = batch();
    return $batch->update($postInstance, $toUpdate, $index);
});



