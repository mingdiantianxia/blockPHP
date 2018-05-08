<?php 
namespace fky;
require_once(__DIR__.'/../inc/template/autoload.php');
class Template extends \terranc\Blade\Factory{
	public function __construct(array $params = array()){
		if (empty($params['ext'])) {
			$params['ext'] = 'html';
		}
		if (empty($params['path'])) {
			$params['path'] = [dirname(__FILE__)."/../../view/"];//数组
		}
		if (empty($params['cachePath'])) {
			$params['cachePath'] = dirname(__FILE__)."/../../data/tpl/";
		}					
		$compiler = new \terranc\Blade\Compilers\BladeCompiler($params['cachePath']);
		//修改定界符，指令符号@也已经在bladeCompiler.php文件中(大约在248、259行)修改成'@>',不解析符不变@
		// $compiler->setRawTags('<{!!','!!}>');
		// $compiler->setContentTags('<{{','}}>');
		// $compiler->setEscapedContentTags('<{{{','}}}>');
		$compiler->directive('datetime', function($timestamp) {
			return "<?php echo date('Y-m-d h:i:s',$timestamp); ?>";
		});
		$engine = new \terranc\Blade\Engines\CompilerEngine($compiler);
		$finder = new \terranc\Blade\FileViewFinder($params['path']);
		$finder->addExtension($params['ext']);		
		parent::__construct($engine, $finder);
	}
	// public function compiler($cachePath){
	// 	return new \terranc\Blade\Compilers\BladeCompiler($cachePath);
	// }
	// public function engine($compiler){
	// 	return new \terranc\Blade\Engines\CompilerEngine($compiler);
	// }
	// public function finder(array $path, $extension = 'html'){
	// 	$finder = new \terranc\Blade\FileViewFinder($path);
	// 	$finder->addExtension($extension);
	// 	return $finder;
	// }
	// public function view($engine, $finder){
	// 	return new \terranc\Blade\Factory($engine, $finder);
	// }	
}

//  $view=loadc('template');
// echo $view->make('hello', ['a' => 1, 'b' => 2, 'time' => time()])->render(); 