<?php

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Overtrue\LaravelPinyin\Facades\Pinyin;

// OK 也代表数据是新增数据
const _NEW = '新数据';
const _ERR = '异常中';
const _OFF = '已停用';

// USED 有过关联的数据, 一般是不可以被删除的
const _USED = '已使用';

define("STATUS_JOIN", implode(',', [_NEW, _OFF, _USED, _ERR]));

const _MAN   = '男';
const _WOMAN = '女';

define("GENDER_JOIN", implode(',', [_MAN, _WOMAN]));

/**
 * @method static array convert(string $data)
 */
class PYin extends Pinyin
{
    static function simple(string $character)
    {
        $runeItem = self::convert($character);
        $value    = '';
        foreach ($runeItem as $runes) {
            $value .= $runes[0];
        }
        return $value;
    }

    static function array(array $arr)
    {
        $item = [];
        foreach ($arr as $character) {
            $item[] = self::simple($character);
        }
        return implode(', ', $item);
    }
}

class Uni
{
    static $lastTime;
    static $sequence;
}

function uni()
{
    return uniqid();
}

// status success
function ss($data = [])
{
    $data['code'] = 200;
    return response($data);
}

// status error
function se($data = [])
{
    $data['code']    = $data['code'] ?? 500;
    $data['message'] = '请求失败请重试';
    return response($data, $data['code']);
}

// transaction
function tx($ok, $onOk = null, $onFail = null)
{
    if ($ok) {
        if ($onOk) {
            $onOk();
        }
        return ss();
    }
    if ($onFail) {
        $onFail();
    }
    return se();
}

function result($data)
{
    return ss(['result' => $data]);
}

function usePage()
{
    /** @var \Illuminate\Http\Request $request */
    $request  = app('request');
    $page     = $request->input('page', 0);
    $pageSize = $request->input('pageSize', 0);
    $columns  = ['*'];
    return [$pageSize, $columns, 'page', $page];
}

function page(LengthAwarePaginator $paginator, $result = [])
{
    $result['pageCount'] = $paginator->lastPage();
    $result['list']      = $paginator->items();
    return result($result);
}

function tree($item)
{
    if ($item instanceof Collection) {
        $item = $item->toArray();
    }
    $result = [];
    for ($i = 0; $i < count($item); $i++) {
        if (!$item[$i]['pid']) {
            $current  = array_splice($item, $i--, 1)[0];
            $children = treeTn($item, $current['id']);
            $model    = [
                'label'  => $current['name'],
                'value'  => $current['id'],
                'key'    => $current['id'],
                'isLeaf' => true
            ];
            if (count($children)) {
                $model['children'] = $children;
                $model['isLeaf']   = false;
            }
            $result[] = array_merge($current, $model);
        }
    }
    return $result;
}

function treeTn($item, $id = '')
{
    $tn = [];
    for ($i = 0; $i < count($item); $i++) {
        if ($item[$i]['pid'] == $id) {
            $current  = array_splice($item, $i--, 1)[0];
            $children = treeTn($item, $current['id']);
            $model    = [
                'label'  => $current['name'],
                'value'  => $current['id'],
                'key'    => $current['id'],
                'isLeaf' => true
            ];
            if (count($children)) {
                $model['children'] = $children;
                $model['isLeaf']   = false;
            }
            $tn[] = array_merge($model, $current);
        }
    }
    return $tn;
}