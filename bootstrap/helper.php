<?php

use App\Exceptions\BurstException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Session;
use Overtrue\LaravelPinyin\Facades\Pinyin;
use Illuminate\Http\Request;

// OK 也代表数据是新增数据
const _NEW = 1;
const _ERR = 3;
const _OFF = 4;

// USED 有过关联的数据, 一般是不可以被删除的
const _USED = 2;
const _STOP = 11;

define("STATUS_JOIN", implode(',', [_NEW, _OFF, _USED, _ERR]));

const _MAN   = 1;
const _WOMAN = 2;

define("GENDER_JOIN", implode(',', [_MAN, _WOMAN]));

function lockMiddleware($name)
{
    return function (Request $request, $next) use ($name) {
        sess($name);
        return $next($request);
    };
}

function padKeys($leftKey, $leftName, $rightKeys, $rightName)
{
    $data = [];
    foreach ($rightKeys as $rightKey) {
        $curr             = [];
        $curr[$leftName]  = $leftKey;
        $curr[$rightName] = $rightKey;
        $data[]           = $curr;
    }
    return $data;
}

function mergeCode(&$post, $field = 'name', $codeField = 'code')
{
    $post[$codeField] = fnPinYin($post[$field]);
}

function rsaDecrypt($data)
{
    $prvKey = file_get_contents(storage_path('app/prv'));
    $data   = base64_decode($data);
    openssl_private_decrypt($data, $text, $prvKey);
    return $text;
}

function rsaEncrypt($data)
{
    $pubKey = file_get_contents(storage_path('app/pub'));
    openssl_public_encrypt($data, $cipherText, $pubKey);
    return base64_encode($cipherText);
}

function passwordSecurity($password)
{
    $strLen = strlen($password);
    if ($strLen > 16) {
        return '请输入一个 8 - 16 位长度易于记忆的密码';
    }
    if ($strLen < 8) {
        return '密码长度不可低于 8 位';
    }
    $rate = 0;
    if (preg_match('@[0-9]+@', $password)) {
        $rate++;
    }
    if (preg_match('@[a-z]+@', $password)) {
        $rate++;
    }
    if (preg_match('@[A-Z]+@', $password)) {
        $rate++;
    }
    if (preg_match('@[^0-9a-zA-Z]+@', $password)) {
        $rate++;
    }
    if ($rate < 3) {
        return '至少需要包括 大写字母、小写字母、数字、特殊字符 中的三项';
    }
    return true;
}

function fnPinYin($data)
{
    return Pinyin::abbr($data, PINYIN_KEEP_NUMBER | PINYIN_KEEP_ENGLISH | PINYIN_KEEP_PUNCTUATION);
}

function tenantId()
{
    if (config('app.tenant')) {
        return sess('tenantId');
    }
    return null;
}

function tenantCode()
{
    if (config('app.tenant')) {
        return sess('tenantCode');
    }
    return null;
}

function sess($name, ...$exceptionInfo)
{
    $eid = Session::get($name);
    if (!$eid) {
        throw new BurstException(...$exceptionInfo);
    }
    return $eid;
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
    $data['message'] = $data['message'] ?? '请求失败请重试';
    return response()->json($data, $data['code']);
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

function upload($options)
{
    /** @var Request $request */
    $request = app('request');
    $file    = $request->file('file');
    $ext     = $file->extension();
    $extType = $options['extType'];
    switch ($extType) {
        case 'image':
            if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
                throw new RuntimeException();
            }
            break;
        case 'video':
            if (!in_array($ext, ['mp4', ''])) {
                throw new RuntimeException();
            }
            break;
        case 'excel':
            if (!in_array($ext, ['csv', 'xlsx'])) {
                throw new RuntimeException();
            }
            break;
        default:
            throw new RuntimeException();
    }
    $eid      = sess('eid');
    $path     = $options['path'];
    $fileId   = uniqid();
    $filename = $file->getClientOriginalName();
    $filename = "$eid-$fileId-$filename";
    $filePath = "$path/$filename";
    $file->move(public_path($path), $filename);
    return $filePath;
}

function resultImg($imgUrl)
{
    return result(['img_url' => $imgUrl]);
}

function usePage()
{
    /** @var Request $request */
    $request  = app('request');
    $page     = $request->input('page', 0);
    $pageSize = $request->input('pageSize', 0);
    $columns  = ['*'];
    return [$pageSize, $columns, 'page', $page];
}

function page(LengthAwarePaginator $paginator, $result = [])
{
    $result['pageCount'] = $paginator->lastPage();
    $result['page']      = $paginator->currentPage();
    $result['pageSize']  = $paginator->perPage();
    $result['list']      = $paginator->items();
    $result['total']     = $paginator->total();
    return result($result);
}

function treeN2Options($item, $foreignKey, $localField = 'name', $foreignField = 'name')
{
    if ($item instanceof Collection) {
        $item = $item->toArray();
    }
    $result = [];
    foreach ($item as $row) {
        if ($row[$foreignKey]) {
            $result[] = [
                'title'      => $row[$localField],
                'value'      => '_' . $row['id'],
                'selectable' => false,
                'children'   => options($row[$foreignKey], $foreignField)
            ];
        }

    }
    return $result;
}

function options($item, $field = 'name')
{
    if ($item instanceof Collection) {
        $item = $item->toArray();
    }
    $result = [];
    foreach ($item as $row) {
        $result[] = [
            'title' => $row[$field],
            'label' => $row[$field],
            'value' => $row['id'],
        ];
    }
    return $result;
}

function treeOptions($item, $selectable = true)
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
                'title'      => $current['name'],
                'value'      => $current['id'],
                'key'        => $current['id'],
                'isLeaf'     => false,
                'selectable' => $selectable,
            ];
            if (count($children) || $i = 0) {
                $model['children']  = $children;
                $model['checkable'] = true;
            } else {
                $model['checkable'] = false;
            }
            $result[] = array_merge($current, $model);
        }
    }
    return $result;
}

function treeTn($item, $id = '', $selectable = true)
{
    $tn = [];
    for ($i = 0; $i < count($item); $i++) {
        if ($item[$i]['pid'] == $id) {
            $current  = array_splice($item, $i--, 1)[0];
            $children = treeTn($item, $current['id']);
            $model    = [
                'title'  => $current['name'],
                'value'  => $current['id'],
                'key'    => $current['id'],
                'isLeaf' => true
            ];
            if (count($children)) {
                $model['children']   = $children;
                $model['isLeaf']     = false;
                $model['selectable'] = $selectable;
            } else {
                $model['checkable'] = false;
            }
            $tn[] = array_merge($model, $current);
        }
    }
    return $tn;
}
