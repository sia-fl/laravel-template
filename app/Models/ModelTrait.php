<?php

namespace App\Models;

use App\Cache\Cache;
use App\Exceptions\PageParamsInvalidException;
use DateTimeInterface;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

/**
 * @mixin Model
 * @method static Builder filter(array $filter)
 */
trait ModelTrait
{
    use Filterable;

    public $status = true;

    static function enabled()
    {
        return static::query()->whereNotIn('status', [_OFF, _ERR]);
    }

    static function cacheOptions()
    {
        $client = Cache::dataCache();
        $name   = static::class . 'Options';
        $data   = $client->get($name);
        if (!$data) {
            $data = static::query()->select(['id', 'name', 'code'])->get();
            $client->set($name, $data);
        }
        return $data;
    }

    static function clearCacheOptions()
    {
        $client = Cache::dataCache();
        $client->del(static::class . 'Options');
    }

    static function indexFilter(array $filter = [])
    {
        /** @var Request $request */
        $request     = app('request');
        $orderBy     = $request->input('sortBy', 'id');
        $orderByDesc = $request->input('sortDirection', 'asc');

        $detect = $request->post('detect');
        if ($detect) {
            $filter['detect'] = $detect;
        }

        $builder = static::filter($filter);
        /** @var Model $model */
        $model = $builder->getModel();
        if ($model->status !== false) {
            $status = $request->input('status', false);
            if ($status) {
                $builder = $builder->whereIn('status', $status);
            } else {
                $builder = $builder->where('status', '!=', _OFF);
            }
        }
        if ($orderByDesc === 'descend') {
            $orderByDesc = 'desc';
        } else if ($orderByDesc === 'ascend') {
            $orderByDesc = 'asc';
        }
        return $builder->orderBy($orderBy, $orderByDesc);
    }

    static function table()
    {
        return (new static())->getTable();
    }

    static function staticQuery($key)
    {
        $query = static::query();
        if (!is_array($key)) {
            $query = $query->where('id', $key);
        } else {
            $query = $query->whereIn('id', $key);
        }
        return $query;
    }

    static function del()
    {
        /** @var Request $request */
        $request = app('request');
        $ids     = $request->input('ids');
        return (self::staticQuery($ids))->where('status', _NEW)->delete();
    }

    static function status()
    {
        /** @var Request $request */
        $request = app('request');
        $ids     = $request->input('ids');
        $status  = $request->input('status');
        return (self::staticQuery($ids)->where('status', '!=', _NEW)->update(['status' => $status]));
    }

    static function used($id)
    {
        return (self::staticQuery($id))->where('status', _NEW)->update(['status' => _USED]);
    }

    static function page(Request $request = null, $whenClosure = null, $columns = ['*'])
    {
        $page     = $request->input('page') ?? 1;
        $pageSize = $request->input('pageSize') ?? 20;
        if (!is_numeric($page) || !is_numeric($pageSize) || $pageSize > 5000) {
            throw new PageParamsInvalidException();
        }
        if ($request instanceof FormRequest) {
            $query = static::filter($request->validated());
        } else {
            $query = static::query();
        }
        if ($whenClosure !== null) {
            $whenClosure($query);
        }
        $result = $query->paginate(
            $pageSize,
            $columns,
            'page',
            $page
        );
        return [
            'result' => [
                'page'      => $page,
                'pageSize'  => $pageSize,
                'pageCount' => $result->lastPage(),
                'total'     => $result->total(),
                'list'      => $result->items()
            ]
        ];
    }


    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
