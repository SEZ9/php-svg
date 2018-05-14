<?php
/**
 * Created by PhpStorm.
 * User: zhangxin
 * Date: 2018/5/7
 * Time: 上午9:15
 */

namespace zhangx1992\PhpSvg;

/**
 * svg图形处理类(基于phpDOM)
 * @version 1.0 created at 2018-05-07
 */

class SvgImg
{
    /**
     * 图片文件存储路径目录
     * @var string
     */
    public $file_save_dir = '/mnt/office/output/images/';

    /**
     * 默认字体大小
     * @var int
     */
    public $font_size = 20;

    /**
     * 字体文件，完整路径
     * @var string
     */
    public $ttf_file = '/data/simsun.ttc';

    private $svg = null;
    private $type = null;

    // 构造
    public function __construct()
    {
        $this->ttf_file = dirname(__FILE__) . '/data/simsun.ttc';
    }

    /**
     * 设置新的画布
     * @param int $canvas_width 画布宽度
     * @param int $canvas_height 画布高度
     * @return boolean
     */
    public function setCanvas($canvas_width, $canvas_height)
    {
        if (! is_int($canvas_width) || $canvas_width <= 0) {
            return false;
        }
        if (! is_int($canvas_height) || $canvas_height <= 0) {
            return false;
        }

        $this->svg  = new \DOMDocument();
        $svg = $this->svg->createElement('svg');
        $svg->setAttribute('xmlns','http://www.w3.org/2000/svg');
        $svg->setAttribute('xmlns:xlink','http://www.w3.org/1999/xlink');
        $svg->setAttribute('width',$canvas_width);
        $svg->setAttribute('height',$canvas_height);
        $this->svg->appendChild($svg);

        return true;
    }

    /**
     * 载入svg图像
     * @param $path
     * @return bool|mixed|null|void
     */
    public function open($path)
    {
        if (!file_exists($path)){
            $this->svg = null;
            return;
        }else{
            //获取文件类型
            $info = $this->getImgInfo($path);
            $type = isset($info['type'])?$info['type']:'';
        }

        if ($type != 'svg' && !empty($info)){
            //点阵图图片转svg
            $this->svg = new \DOMDocument();
            $svg = $this->svg->createElement('svg');
            $svg->setAttribute('xmlns','http://www.w3.org/2000/svg');
            $svg->setAttribute('xmlns:xlink','http://www.w3.org/1999/xlink');
            $svg->setAttribute('width',$info['width']);
            $svg->setAttribute('height',$info['height']);
            $image = $this->svg->createElement('image');
            $image->setAttribute('width',$info['width']);
            $image->setAttribute('height',$info['height']);
            $image->setAttribute('x',0);
            $image->setAttribute('y',0);
            $image->setAttribute('xlink:href','data:image/png;base64,'.base64_encode(file_get_contents($path)));
            $svg->appendChild($image);
            $this->svg->appendChild($svg);

        }else{
            //载入svg文档
            $this->svg = new \DOMDocument();
            $this->svg->load($path);
        }

        return $this->svg;
    }

    /**
     * 更改图像大小
     * @param int $width 新的宽度
     * @param int $height 新的高度
     * @param string $fit 适应大小
     * 'force': 把图像强制改为$width X $height
     * 'scale': 按比例在$width X $height内缩放图片,结果不完全等於$width X $height
     * 'scale_fill':按比例在$width X $height内缩放图片,没有像素的地方填充顏色$fill_color=array(255,255,255)(红,绿,蓝,透明度[0不透明-127全透明])
     * 其他:智能模式,缩放图片并从正中裁切$width X $height的大小
     * 注意:
     * $fit='force','scale','scale_fill'时输出完整图像
     * $fit=图像方位时输出指定位置部份的图像
     * 字母与图像的对应关系如下:
     * north_west north north_east
     * west center east
     * south_west south south_east
     * @param array $fill_color
     */
    public function resizeTo($width = 100, $height = 100, $fit = 'center', $fill_color = array(255,255,255,0))
    {
        switch ($fit){
            case 'force':
            case 'scale':
            case 'scale_fill':
                $this->thumbnail($width,$height,false);
                break;
            default:
                //获取原图尺寸
                $origWidth = $this->get_width();
                $origHgight = $this->get_height();
                $crop_x = 0;
                $crop_y = 0;
                $crop_w = $origWidth;
                $crop_h = $origHgight;
                //裁剪框大小
                if ($origWidth * $height > $origHgight * $width) {
                    $crop_w = intval($origHgight * $width / $height);
                } else {
                    $crop_h = intval($origWidth * $height / $width);
                }
                //裁剪起始位置
                switch ($fit) {
                    case 'north_west':
                        $crop_x = 0;
                        $crop_y = 0;
                        break;
                    case 'north':
                        $crop_x = intval(($origWidth - $crop_w) / 2);
                        $crop_y = 0;
                        break;
                    case 'north_east':
                        $crop_x = $origWidth - $crop_w;
                        $crop_y = 0;
                        break;
                    case 'west':
                        $crop_x = 0;
                        $crop_y = intval(($origHgight - $crop_h) / 2);
                        break;
                    case 'center':
                        $crop_x = intval(($origWidth - $crop_w) / 2);
                        $crop_y = intval(($origHgight - $crop_h) / 2);
                        break;
                    case 'east':
                        $crop_x = $origWidth - $crop_w;
                        $crop_y = intval(($origHgight - $crop_h) / 2);
                        break;
                    case 'south_west':
                        $crop_x = 0;
                        $crop_y = $origHgight - $crop_h;
                        break;
                    case 'south':
                        $crop_x = intval(($origWidth - $crop_w) / 2);
                        $crop_y = $origHgight - $crop_h;
                        break;
                    case 'south_east':
                        $crop_x = $origWidth - $crop_w;
                        $crop_y = $origHgight - $crop_h;
                        break;
                    default:
                        $crop_x = intval(($origWidth - $crop_w) / 2);
                        $crop_y = intval(($origHgight - $crop_h) / 2);
                }
                //裁剪
                $rect = $this->svg->createElement('rect');
                $rect->setAttribute('x',$crop_x);
                $rect->setAttribute('y',$crop_y);
                $rect->setAttribute('width',$crop_w);
                $rect->setAttribute('height',$crop_h);
                $clip = $this->svg->createElement('clipPath');
                $clip->appendChild($rect);
                //唯一id
                $id = $this->getRandCode();
                $clip->setAttribute('id',$id);
                $defs = $this->svg->createElement('defs');
                $defs->appendChild($clip);
                $root = $this->svg->getElementsByTagName('svg');
                foreach ($root as $new){
                    $new->appendChild($defs);
                    $new->setAttribute('clip-path','url(#'.$id.')');
                }
        }
    }

    /**
     * 添加图片水印
     * @param $path 水印图片(包含完整路径)
     * @param int $x 水印x坐标
     * @param int $y 水印y坐标
     * @param int $opacity 水印图片透明度：百分制，100表示完全不透明
     * @return bool
     */
    public function addWatermark($path,$x=0,$y=0,$opacity = 100)
    {
        if (!is_file($path) || !file_exists($path)) {
            return false;
        }else{
            $file = base64_encode(file_get_contents($path));
        }
        //获取svg根节点
        $svgNode = $this->svg->getElementsByTagName('svg');
        foreach ($svgNode as $new) {
            //水印添加
            $newNode = $this->svg->createElement('image');
            $newNode->setAttribute('xlink:href', 'data:image/png;base64,'.$file);
            $newNode->setAttribute('x', $x);
            $newNode->setAttribute('y', $y);
            $newNode->setAttribute('width', $this->getImgWidth());
            $newNode->setAttribute('height', $this->getImgHight());
            $newNode->setAttribute('fill-opacity', $opacity);
            $g = $this->svg->createElement('g');
            $g->appendChild($newNode);
            $new->appendChild($g);
        }
        return true;
    }

    /**
     * 添加文字水印
     * @param string $text 水印文字
     * @param int $x 水印x坐标
     * @param int $y 水印y坐标
     * @param int $angle 文本写入角度
     * @param int $width 根据文本块宽度自动换行：0不换行
     * @param array $style 文本样式['font' => 字体, 'font_size', 'fill_color', 'under_color']
     * @param int $gravity 坐标定位参考点，默认为左上角
     * @return bool
     */
    public function addText($text, $x = 0, $y = 0, $angle = 0, $width = 0, $style = array(), $gravity=0)
    {
        $svgNode = $this->svg->getElementsByTagName('svg');
        $newNode = $this->svg->createElement('text');
        // 文本换行
        if ($width > 0) {
            // 字体大小
            $font_size = 0;
            if (isset($style['font_size'])) {
                $font_size = $style['font_size'];
            } else {
                $font_size = $this->font_size;
            }
            // 字体文件
            if (isset($style['font'])) {
                $font_file = $style['font'];
            } else {
                $font_file = $this->ttf_file;
            }
            // 计算文本换行
            $text = Char::autowrap($font_size, $font_file, $text, $width);
        }
        $newNode->setAttribute('fill',isset($style['fill_color'])?$style['fill_color']:'black'); //颜色
        $newNode->setAttribute('font-size',isset($style['font_size'])?$style['font_size']:24);  //字号
        $newNode->setAttribute('x',$x);  //x坐标
        $newNode->setAttribute('y',$y);  //y坐标
        if ($gravity || $angle){
            $newNode->setAttribute('transform','rotate('.$angle.' '.$gravity.')');
        }
        $info = $this->svg->createTextNode($text);
        $newNode->appendChild($info);
        $g = $this->svg->createElement('g');
        $g->appendChild($newNode);
        foreach ($svgNode as $new){
            $new->appendChild($g);
        }

        return true;
    }

    /**
     * 添加渐变色文本
     * @param string $text 水印文字
     * @param int $x 水印x坐标
     * @param int $y 水印y坐标
     * @param int $angle 文本写入角度
     * @param array $style 文本样式['font' => 字体, 'font_size', 'fill_color', 'under_color']
     * @param int $gravity 坐标定位参考点，默认为左上角
     * @return boolean
     */
    public function addTextGradient($text, $x, $y, $angle, $style = [], $gravity = Imagick::GRAVITY_NORTHWEST)
    {
        //添加渐变色
        $stop1 = $this->svg->createElement('stop');
        $stop1->setAttribute('offset','0%');
        $stop1->setAttribute('style','stop-color:#999999;stop-opacity:0');
        $grandient = $this->svg->createElement('radialGradient');
        //唯一id
        $id = $this->getRandCode();
        $grandient->setAttribute('id',$id);
        $grandient->appendChild($stop1);
        $stop2 = $this->svg->createElement('stop');
        $stop2->setAttribute('offset','100%');
        $stop2->setAttribute('style','stop-color:#ffffff;stop-opacity:1');
        $grandient->appendChild($stop2);
        $defs = $this->svg->createElement('defs');
        $defs->appendChild($grandient);
        //添加文本 应用渐变色
        $newText = $this->svg->createElement('text');
        $fontSize = isset($style['font-size'])?$style['font-size']:24;
        $fontFamily = isset($style['font-family'])?$style['font-size']:'';
        $newText->setAttribute('font-size',$fontSize);
        $newText->setAttribute('font-family',$fontFamily);
        $newText->setAttribute('fill','url(#'. $id .')');
        $newText->setAttribute('x',$x);
        $newText->setAttribute('y',$y);
        if ($gravity || $angle){
            $newText->setAttribute('transform','rotate('.$angle.' '.$gravity.')');
        }
        $info = $this->svg->createTextNode($text);
        $newText->appendChild($info);
        $svgNode = $this->svg->getElementsByTagName('svg');
        $g = $this->svg->createElement('g');
        $g->appendChild($newText);
        foreach ($svgNode as $new){
            $new->appendChild($defs);
            $new->appendChild($g);
        }
        return true;
    }

    /**
     * 图片存档
     * @param string $path 存档的位置和新的档案（相对路径）
     * @return bool 成功返回存储文件名，失败返回false
     */
    public function saveTo($path)
    {
        if (empty($this->file_save_dir)) {
            return false;
        }
        if (! file_exists($this->file_save_dir)) {
            mkdir($this->file_save_dir, 0755, true);
        }
        $path = $this->file_save_dir . '/' . $path;
        file_put_contents($path,$this->svg->saveXML());
        return $path;
    }

    /**
     * 直接输出图像到浏览器
     * @param bool $header
     */
    public function output($header = true)
    {
        if ($header) {
            header('Content-type: image/svg+xml');
        }
        echo $this->svg->saveXML();
    }

    /**
     * 建立缩略图
     * @param int $width 图像宽度
     * @param int $height 图像高度
     * @param bool $fit 为真时,将保持比例并在$width X $height内产生缩略图
     */
    public function thumbnail($width = 100, $height = 100, $fit = true)
    {
        $svg = $this->svg->getElementsByTagName('svg');
        foreach ($svg as $root)
        {
            $root->setAttribute('width',$width);
            $root->setAttribute('height',$height);
        }
        $this->svg->saveXML();
    }

    /**
     * 获取图片内容
     * @return string
     */
    public function getImgBlob()
    {
        return $this->svg->saveXML();
    }

    /**
     * 取得图像宽度
     */
    public function get_width()
    {
        $svg = $this->svg->getElementsByTagName('svg');
        foreach ($svg as $root)
        {
            $width = $root->getAttribute('width');
            //$height = $root->getAttribute('height');
        }
        return $width;
    }

    /**
     * 取得图像高度
     */
    public function get_height()
    {
        $svg = $this->svg->getElementsByTagName('svg');
        foreach ($svg as $root)
        {
            //$width = $root->getAttribute('width');
            $height = $root->getAttribute('height');
        }
        return $height;
    }

    /**
     * 取得图像类型
     */
    public function get_type()
    {
        return 'svg';
    }

    /**
     * 设置全局默认字体，影响全局
     * @param string $font
     */
    public function setFont($font = 'simsun.ttc')
    {
        if (file_exists($font) && is_file($font)) {
            $this->ttf_file = $font;
        } else {
            $this->ttf_file = dirname(__FILE__) . '/data/' . $font;
        }
    }

    /**
     * 获取字体路径，没有则返回默认字体
     * @param string $font
     * @return string
     */
    public static function getFontFullPath($font = 'simsun.ttc')
    {
        $font_path = dirname(__FILE__) . '/data/' . $font;
        if (is_file($font_path)) {
            return $font_path;
        } else {
            return '';
        }
    }


    /**
     * 绘制矩形框
     * @param string $strokeColor 边框颜色
     * @param string $fillColor 矩形框填充色
     * @param int $startX 矩形框左上角x坐标值
     * @param int $startY 矩形框左上角y坐标值
     * @param int $endX 矩形框右下角x坐标值
     * @param int $endY 矩形框右下角y坐标值
     * @param int $roundX 圆角x值
     * @param int $roundY 圆角y值
     * @return boolean
     */
    public function setRoundRectangle($strokeColor, $fillColor, $startX, $startY, $endX, $endY, $roundX, $roundY)
    {
        $svg = $this->svg->getElementsByTagName('svg');
        $newNode = $this->svg->createElement('rect');
        $newNode->setAttribute('x',$startX);
        $newNode->setAttribute('y',$startY);
        $newNode->setAttribute('width',($endX-$startX));
        $newNode->setAttribute('height',($endY-$startY));
        $newNode->setAttribute('rx',$roundX);
        $newNode->setAttribute('ry',$roundY);
        $newNode->setAttribute('style','fill:'.$fillColor.';'.'stroke:'.$strokeColor);
        $g = $this->svg->createElement('g');
        $g->appendChild($newNode);
        foreach ($svg as $new){
            $new->appendChild($g);
        }
        return true;
    }

    /**
     * 生成随机字符串
     * @return string
     */
    public function getRandCode()
    {
        $code = '';
        for($i=1;$i<6;$i++){
            $code.=rand(0,9);
        }

        return md5($code.time());
    }

    /**
     * 获取普通格式图片宽、高、类型
     */
    public function getImgInfo($path)
    {
        $info =  getimagesize($path);
        if (!empty($info)){
            return [
                'width' => $info[0],
                'height' => $info[1],
                'mime' => $info['mime']
            ];
        }else{
            return false;
        }

    }
}
