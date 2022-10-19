<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/* Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return response()->json($request->user());
}); */

Route::get('/test', function () {
    $content = 'Ngay từ khi còn là một gã bộ binh trong Quân đoàn Red Mist, Mogul Khan đã xác định mục tiêu của mình là trở thành tướng quân của Red Mist. Trải qua không biết bao nhiêu trận chiến, hắn đã dùng những chiến thắng đẫm máu để khẳng định giá trị và địa vị của mình. Mogul Khan liên tục thăng tiến với việc không ngần ngại sát hại thượng cấp của mình. Trải qua 7 năm của chiến dịch Thousand Tarns, hắn vinh danh tên mình bằng những cuộc tàn sát đẫm máu, khiến danh vọng của hắn trở nên sáng chói hơn bao giờ hết, trong khi những người đồng đội bên cạnh hắn lần lượt ít dần. Vào cái đêm chiến thắng cuối cùng, Axe tuyên bố hắn chính là vị tướng quân mới của quân đoàn Red Mist, cũng tự phong cho mình danh hiệu “Axe”. Thế nhưng, quân đội của hắn chỉ còn là con số không. Tất nhiên, rất nhiều người đã tử vong trong chiến trận, nhưng càng nhiều người khác lại đã chết dưới chính lưỡi búa của Axe. Một điều hiển nhiên rằng, hiện tại, hầu hết các chiến sĩ đều lảng tránh sự lãnh đạo của Axe. Nhưng đối với hắn mà nói, điều đó chẳng hề có nghĩa lí gì hết. Bởi lẽ, với Axe, quân-đoàn-một-người mới là quân đoàn mạnh nhất.';

    return response()->json($content);
});

Route::prefix('auth')->group(function () {
    Route::get('/user', [AuthController::class, 'show'])->middleware('auth:sanctum');
    Route::post('/signup', [AuthController::class, 'signup']);
    Route::post('/signin', [AuthController::class, 'signin']);
    Route::delete('/signout', [AuthController::class, 'signout'])->middleware('auth:sanctum');
});

Route::post('/test-csrf', function() {
    return 'oh my god!!!';
})->middleware('auth:sanctum');
