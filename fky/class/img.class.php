<?php 
namespace fky;
require_once(__DIR__.'/../inc/img/autoload.php');
use Gregwar\Image\Image;
class Img extends Image{

}

// 缩略图
// loadc('img')->open('mona.jpg')
//      ->cropResize(200, 50)
//      ->save('out.jpg');
// 裁剪
// loadc('img')->open('mona.jpg')
//   ->zoomCrop(200, 200, 'transparent', 'center', 'top')
//   ->png();
// 合并
// $pic = loadc('img')->open('img/mona.jpg');
// $watermark = loadc('img')->open('img/vinci.png');
// $pic->merge($watermark, $pic->width()-$watermark->width(),
//     $pic->height()-$watermark->height())
//     ->save('out.jpg', 'jpg');