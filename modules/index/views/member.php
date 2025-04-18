<?php
/**
 * @filesource modules/index/views/member.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Member;

use Kotchasan\DataTable;
use Kotchasan\Date;
use Kotchasan\Http\Request;

/**
 * module=member
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * @var object
     */
    private $category;

    /**
     * ตารางรายชื่อสมาชิก
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ค่าที่ส่งมา
        $params = [
            'status' => $request->request('status', -1)->toInt()
        ];
        // สถานะสมาชิก
        $member_status = [-1 => '{LNG_all items}'];
        foreach (self::$cfg->member_status as $key => $value) {
            $member_status[$key] = '{LNG_'.$value.'}';
        }
        $filters = [];
        // หมวดหมู่
        $this->category = \Index\Category\Model::init(false, true, false);
        foreach ($this->category->items() as $k => $label) {
            if (!$this->category->isEmpty($k)) {
                $params[$k] = $request->request($k)->topic();
                $filters[] = [
                    'name' => $k,
                    'text' => $label,
                    'options' => ['' => '{LNG_all items}'] + $this->category->toSelect($k),
                    'value' => $params[$k]
                ];
            }
        }
        $filters[] = [
            'name' => 'status',
            'text' => '{LNG_Member status}',
            'options' => $member_status,
            'value' => $params['status']
        ];
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'index.php');
        // ตาราง
        $table = new DataTable([
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => \Index\Member\Model::toDataTable($params),
            /* รายการต่อหน้า */
            'perPage' => $request->cookie('member_perPage', 30)->toInt(),
            /* เรียงลำดับ */
            'sort' => $request->cookie('member_sort', 'id desc')->toString(),
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => [$this, 'onRow'],
            /* คอลัมน์ที่สามารถค้นหาได้ */
            'searchColumns' => ['name', 'username', 'phone'],
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/index/model/member/action',
            'actionCallback' => 'dataTableActionCallback',
            'actions' => [
                [
                    'id' => 'action',
                    'class' => 'ok',
                    'text' => '{LNG_With selected}',
                    'options' => [
                        // ส่งแจ้งเตือนอนุมัติการเข้าระบบ
                        'active_2' => '{LNG_Send login approval notification}',
                        // ส่งคำขอ ยืนยันสมาชิก
                        'activate_0' => '{LNG_Send member confirmation message}',
                        // ส่งคำขอ ขอรหัสผ่านใหม่
                        'sendpassword' => '{LNG_Send a new password request}',
                        // ยอมรับคำขอยืนยันสมาชิก
                        'activate_1' => '{LNG_Accept member verification request}',
                        // สามารถเข้าระบบได้
                        'active_1' => '{LNG_Can login}',
                        // ไม่สามารถเข้าระบบได้
                        'active_0' => '{LNG_Can&#039;t login}',
                        // ลบ
                        'delete' => '{LNG_Delete}'
                    ]
                ]
            ],
            /* ตัวเลือกด้านบนของตาราง ใช้จำกัดผลลัพท์การ query */
            'filters' => $filters,
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => [
                'id' => [
                    'text' => '',
                    'sort' => 'id'
                ],
                'username' => [
                    'text' => self::usernameLabel()
                ],
                'name' => [
                    'text' => '{LNG_Name}',
                    'sort' => 'name'
                ],
                'active' => [
                    'text' => '',
                    'class' => 'center notext',
                    'sort' => 'active'
                ],
                'activatecode' => [
                    'text' => ''
                ],
                'social' => [
                    'text' => ''
                ],
                'phone' => [
                    'text' => '{LNG_Phone}'
                ],
                'create_date' => [
                    'text' => '{LNG_Created}',
                    'class' => 'center'
                ],
                'status' => [
                    'text' => '{LNG_Member status}',
                    'class' => 'center'
                ]
            ],
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => [
                'name' => [
                    'class' => 'nowrap'
                ],
                'active' => [
                    'class' => 'center'
                ],
                'activatecode' => [
                    'class' => 'center'
                ],
                'social' => [
                    'class' => 'center'
                ],
                'create_date' => [
                    'class' => 'center nowrap'
                ],
                'status' => [
                    'class' => 'center'
                ]
            ],
            /* ปุ่มแสดงในแต่ละแถว */
            'buttons' => [
                [
                    'class' => 'icon-edit button green',
                    'href' => $uri->createBackUri(['module' => 'editprofile', 'id' => ':id']),
                    'text' => '{LNG_Edit}'
                ]
            ],
            /* ปุ่มเพิม */
            'addNew' => [
                'class' => 'float_button icon-register',
                'href' => $uri->createBackUri(['module' => 'register', 'id' => 0]),
                'title' => '{LNG_Register}'
            ]
        ]);
        // save cookie
        setcookie('member_perPage', $table->perPage, time() + 2592000, '/', HOST, HTTPS, true);
        setcookie('member_sort', $table->sort, time() + 2592000, '/', HOST, HTTPS, true);
        // คืนค่า HTML
        return $table->render();
    }

    /**
     * จัดรูปแบบการแสดงผลในแต่ละแถว
     *
     * @param array  $item ข้อมูลแถว
     * @param int    $o    ID ของข้อมูล
     * @param object $prop กำหนด properties ของ TR
     *
     * @return array คืนค่า $item กลับไป
     */
    public function onRow($item, $o, $prop)
    {
        foreach ($this->category->items() as $k => $label) {
            if (isset($item[$label])) {
                if (in_array($k, self::$cfg->categories_multiple)) {
                    $ds = [];
                    foreach (explode(',', $item[$label]) as $d) {
                        $ds[] = $this->category->get($k, $d);
                    }
                    $item[$label] = implode(',', $ds);
                } else {
                    $item[$label] = $this->category->get($k, $item[$label]);
                }
            }
        }
        $item['create_date'] = Date::format($item['create_date'], 'd M Y');
        if ($item['active'] == 1) {
            $item['active'] = '<span class="icon-valid notext access" title="{LNG_Can login}"></span>';
        } else {
            $item['active'] = '<span class="icon-valid notext disabled" title="{LNG_Can&#039;t login}"></span>';
        }
        if ($item['activatecode'] != '') {
            $item['activatecode'] = '<span class="icon-verfied notext access" title="{LNG_Membership has not been confirmed yet.}"></span>';
        }
        $social = [
            1 => 'facebook',
            2 => 'google',
            3 => 'line',
            4 => 'telegram'
        ];
        if (isset($social[$item['social']])) {
            $item['social'] = '<span class="icon-'.$social[$item['social']].' notext"></span>';
        } else {
            $item['social'] = '';
        }
        $item['status'] = isset(self::$cfg->member_status[$item['status']]) ? '<span class=status'.$item['status'].'>{LNG_'.self::$cfg->member_status[$item['status']].'}</span>' : '';
        $item['phone'] = self::showPhone($item['phone']);
        if (is_file(ROOT_PATH.DATA_FOLDER.'avatar/'.$item['id'].self::$cfg->stored_img_type)) {
            $avatar = WEB_URL.DATA_FOLDER.'avatar/'.$item['id'].self::$cfg->stored_img_type;
            $avatar = '<img class=user_icon src="'.$avatar.'" alt="{LNG_Avatar}">';
        } else {
            $username = empty($item['username']) ? $item['name'] : $item['username'];
            if ($username == '') {
                $avatar = '<img class=user_icon src="'.WEB_URL.'skin/img/noicon.png" alt="{LNG_Avatar}">';
            } else {
                $avatar = '<span class=user_icon data-letters="'.mb_substr($username, 0, 2).'"></span>';
            }
        }
        $item['username'] = empty($item['username']) ? '' : '<a id=login_'.$item['id'].' class=icon-signin title="{LNG_Login as} '.$item['name'].'">'.$item['username'].'</a>';
        $item['id'] = $avatar;
        return $item;
    }
}
