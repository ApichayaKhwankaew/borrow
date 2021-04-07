<?php
/**
 * @filesource modules/borrow/models/inventory.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Borrow\Inventory;

use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=borrow-inventory
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Query ข้อมูลสำหรับส่งให้กับ DataTable
     *
     * @param array $params
     *
     * @return \Kotchasan\Database\QueryBuilder
     */
    public static function toDataTable($params)
    {
        $where = array(
            array('V.inuse', 1),
        );
        if ($params['category_id'] > 0) {
            $where[] = array('V.category_id', $params['category_id']);
        }
        if ($params['model_id'] > 0) {
            $where[] = array('V.model_id', $params['model_id']);
        }
        if ($params['type_id'] > 0) {
            $where[] = array('V.type_id', $params['type_id']);
        }
        return static::createQuery()
            ->select('V.id', 'I.product_no', 'V.topic', 'V.category_id', 'V.type_id', 'V.model_id', 'I.stock', 'I.unit')
            ->from('inventory V')
            ->join('inventory_items I', 'LEFT', array('I.inventory_id', 'V.id'))
            ->where($where)
            ->andWhere(array(
                array('I.stock', '>', 0),
                array('V.count_stock', 0),
            ), 'OR');
    }

    /**
     * อ่านข้อมูลรายการที่เลือก
     * คืนค่าข้อมูล object ไม่พบคืนค่า null
     *
     * @param int  $id     ID
     *
     * @return object|null
     */
    public static function get($id)
    {
        return static::createQuery()
            ->from('inventory V')
            ->join('inventory_items I', 'LEFT', array('I.inventory_id', 'V.id'))
            ->join('inventory_meta D', 'LEFT', array(array('D.inventory_id', 'V.id'), array('D.name', 'detail')))
            ->where(array('V.id', $id))
            ->first('V.id', 'V.topic', 'V.category_id', 'V.type_id', 'V.model_id', 'I.product_no', 'D.value detail', 'I.stock', 'I.unit');
    }

    /**
     * รับค่าจาก action
     *
     * @param Request $request
     */
    public function action(Request $request)
    {
        $ret = array();
        // session, referer, Ajax
        if ($request->initSession() && $request->isReferer() && $request->isAjax()) {
            $action = $request->post('action')->toString();
            if ($action === 'detail') {
                // แสดงรายละเอียด
                $search = self::get($request->post('id')->toInt());
                if ($search) {
                    $ret['modal'] = \Borrow\Detail\View::details($search);
                }
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่า JSON
        echo json_encode($ret);
    }
}
