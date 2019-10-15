<?php
namespace controllers\worker;
use controllers\BaseController;
use fky\classs\Phpredis;
use fky\traits\Tools;

/**
* \HomeController
*/
class TestController extends BaseController
{
    use Tools;

    public function test()
  {
      $dbInstance = loadc('db')->pdo;
      $dbInstance->beginTransaction();
      try{
          $sql = "SELECT id,point FROM wx_lewaimai_order_customer WHERE id = 1";
          $data = $dbInstance->query($sql, \PDO::FETCH_ASSOC)->fetch();

          $redis = Phpredis::getInstance();
//          $num = $redis->incr('test_point_transaction',10);
          $num = $this->createRandomStr(2, true);
          $redis->setNx('test_point_transaction_total', $data['point']);
          $totalPoint = $redis->incr('test_point_transaction_total', $num);

          $point = $data['point']+$num;

          $sql1 = "UPDATE wx_lewaimai_order_customer SET point = ".$totalPoint." WHERE id = 1 ";
          $queryR = $dbInstance->exec($sql1);
          if (!$queryR) {
              $dbInstance->rollBack();
              echo '更新错误';
              var_dump($dbInstance->errorInfo());
          }

          echo PHP_EOL.date("Y-m-d H:i:s").' 增加积分：'.$num.' oldPoint:'.$data['point']. ' newPoint:'.$point . ' totalPoint:'.$totalPoint .PHP_EOL;
          loadc('log')->info(date("Y-m-d H:i:s").' 增加积分：'.$num.' oldPoint:'.$data['point']. ' newPoint:'.$point . ' totalPoint:'.$totalPoint);
          $dbInstance->commit();
      }catch (\Exception $e) {
          $dbInstance->rollBack();
          echo $e->getMessage();
      }

      return true;
  }


}