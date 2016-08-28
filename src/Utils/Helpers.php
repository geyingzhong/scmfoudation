<?php
namespace geyingzhong\Utils;
use App\Models\Image;
use App\Models\Module;
use App\Models\Permission;
use App\Models\RolePermission;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;


class Helpers
{
    /**
     * ceil the units of a integer which is bigger than units.
     *
     * @param int $digit
     * @return int
     */
    static function ceil10($digit)
    {
        $str = strval(ceil($digit));
        $len = strlen($str);
        if ($str[$len - 1] != 0) {
            $str[$len - 1] = 0;
            $str[$len - 2] = $str[$len - 2] + 1;
        }
        return intval($str);
    }

    /**
     * get the extension for a file
     *
     * @param String $fileName
     * @return extension of a file. like: jpg or PNG or txt or php
     */
    static function ext($fileName)
    {
        return substr(strrchr($fileName, '.'), 1);
    }

    /**
     * get a pager html render
     *
     * @param Array $p
     * array(
     *        'base'  => 'base url, like: product/list',
     *        'cnt' => 'total items count',
     *        'cur'   => 'current page id',
     *        'size' => 'Optional, item count per page',
     *        'span' => 'Optional, gap count between pager button',
     * )
     *
     * @return Array {
     *        'start'=>'the start offset in queryLimit',
     *        'rows'=>'rows to fetch in queryLimit',
     *        'html'=>'page html render, e.g. 1  3 4 5 6  8'
     * }
     */
    static function pager(array $p)
    {
        //==parse page variables
        if (empty($p['size'])) $p['size'] = PAGE_SIZE;
        if (empty($p['span'])) $p['span'] = PAGE_SPAN;

        //==if $p['base'] is not trailing with / or = (like user/list/ or user/list/?p=),
        //add / to the end of base. eg. p[base] = user/list to user/list/.
        $pBaseLastChar = substr($p['base'], -1);
        if ($pBaseLastChar != '/' && $pBaseLastChar != '=') $p['base'] .= '/';

        if ($p['cnt'] <= 0) {
            return array('start' => 0, 'rows' => 0, 'html' => '');
        }

        if (($p['cnt'] % $p['size']) == 0) {
            $p['total'] = $p['cnt'] / $p['size'];
        } else {
            $p['total'] = floor($p['cnt'] / $p['size']) + 1;
        }
        //if only have one page don't show the pager
        if ($p['total'] == 1) return array('start' => 0, 'rows' => 0, 'html' => '');

        if (isset($p['cur'])) {
            $p['cur'] = intval($p['cur']);
        } else {
            $p['cur'] = 1;
        }
        if ($p['cur'] < 1) {
            $p['cur'] = 1;
        }
        if ($p['cur'] > $p['total']) {
            $p['cur'] = $p['total'];
        }

        if ($p['total'] <= $p['span'] + 1) {
            $p['start'] = 1;
            $p['end'] = $p['total'];
        } else {
            if ($p['cur'] < $p['span'] + 1) {
                $p['start'] = 1;
                $p['end'] = $p['start'] + $p['span'];
            } else {
                $p['start'] = $p['cur'] - $p['span'] + 1;
                if ($p['start'] > $p['total'] - $p['span']) $p['start'] = $p['total'] - $p['span'];
                $p['end'] = $p['start'] + $p['span'];
            }
        }
        if ($p['start'] < 1) $p['start'] = 1;
        if ($p['end'] > $p['total']) $p['end'] = $p['total'];


        $p['offset'] = ($p['cur'] - 1) * $p['size'];


        //==render with html
        $html = '';
        if ($p['start'] != 1) {
            $html .= '<a href="' . url($p['base'] . '1') . '" class="p">1</a>';
            if ($p['start'] - 1 > 1) $html .= '&bull;&bull;';
        }
        for ($i = $p['start']; $i <= $p['end']; $i++) {
            if ($p['cur'] == $i) {
                $html .= '<strong class="p_cur">' . $i . '</strong>';
            } else {
                $html .= '<a href="' . url($p['base'] . $i) . '" class="p">' . $i . '</a>';
            }
        }
        if ($p['end'] != $p['total']) {
            if ($p['total'] - $p['end'] > 1) $html .= '&bull;&bull;';
            $html .= '<a href="' . url($p['base'] . $p['total']) . '" class="p">' . $p['total'] . '</a>';
        }
        $html .= '<strong class="p_info">' . $p['cnt'] . '&nbsp' . 'total items | ' . $p['size'] . '&nbsp' . 'items each page</strong>';

        return array('start' => $p['offset'], 'rows' => $p['size'], 'html' => $html, '');
    }

    /**
     * return the right new line of the web server:
     * Unix: \n
     * Win: \r\n
     * Mac: \r
     *
     */
    static function nl()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? "\r\n" : "\n";
    }

    /**
     * return current user's real IP. It can get IP behind Proxy.
     */
    static function realIp()
    {
        static $realIp = '';

        if (!$realIp) {
            $cip = getenv('HTTP_CLIENT_IP');
            $xip = getenv('HTTP_X_FORWARDED_FOR');
            $rip = getenv('REMOTE_ADDR');
            $srip = $_SERVER['REMOTE_ADDR'];
            if ($cip && strcasecmp($cip, 'unknown')) {
                $realIp = $cip;
            } elseif ($xip && strcasecmp($xip, 'unknown')) {
                $realIp = $xip;
            } elseif ($rip && strcasecmp($rip, 'unknown')) {
                $realIp = $rip;
            } elseif ($srip && strcasecmp($srip, 'unknown')) {
                $realIp = $srip;
            }
            $match = array();
            preg_match('/[\d\.]{7,15}/', $realIp, $match);
            $realIp = $match[0] ? $match[0] : '0.0.0.0';
        }
        return $realIp;
    }

    /**
     * refine a size data
     *
     * @param string $size
     * @param int $fix
     * @return string
     */
    static function refineSize($size, $fix = 2)
    {
        if ($size < 1024) return round($size, $fix) . ' B'; //<1K
        elseif ($size < 1048576) return round($size / 1024, $fix) . ' KB'; //<1M
        elseif ($size < 1073741824) return round($size / 1048576, $fix) . ' MB'; //<1G
        else return round($size / 1073741824, $fix) . ' GB';
    }

    static function addSuffix($FileName, $Suffix)
    {
        $ext = strrchr($FileName, '.');

        if (!$ext)
            return $FileName . $Suffix;

        return substr($FileName, 0, strpos($FileName, '.')) . $Suffix . $ext;
    }

    static function debug($var, $print_r = true)
    {
        echo '<pre>';
        $print_r ? print_r($var) : var_dump($var);
        echo '</pre>';
    }

    /**
     * Divide an array into two arrays. One with keys and the other with values.
     *
     * @param array $array
     * @return array
     */
    static function array_divide($array)
    {
        return array(array_keys($array), array_values($array));
    }

    /**
     * Flatten a multi-dimensional associative array with dots.
     *
     * @param array $array
     * @param string $prepend
     * @return array
     */
    static function array_dot($array, $prepend = '')
    {
        $results = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $results = array_merge($results, array_dot($value, $prepend . $key . '.'));
            } else {
                $results[$prepend . $key] = $value;
            }
        }
        return $results;
    }

    /**
     * Get all of the given array except for a specified array of items.
     *
     * @param array $array
     * @param array $keys
     * @return array
     */
    static function array_except($array, $keys)
    {
        return array_diff_key($array, array_flip((array)$keys));
    }

    /**
     * Return the first element in an array passing a given truth test.
     *
     * @param  array $array
     * @param  Closure $callback
     * @param  mixed $default
     * @return mixed
     */
    static function array_first($array, $callback, $default = null)
    {
        foreach ($array as $key => $value) {
            if (call_user_func($callback, $key, $value)) return $value;
        }

        return $default;
    }

    /**
     * Return the last element in an array passing a given truth test.
     *
     * @param  array $array
     * @param  Closure $callback
     * @param  mixed $default
     * @return mixed
     */
    static function array_last($array, $callback, $default = null)
    {
        return array_first(array_reverse($array), $callback, $default);
    }

    /**
     * Remove an array item from a given array using "dot" notation.
     *
     * @param  array $array
     * @param  string $key
     * @return void
     */
    static function array_forget(&$array, $key)
    {
        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);
            if (!isset($array[$key]) || !is_array($array[$key])) {
                return;
            }
            $array =& $array[$key];
        }
        unset($array[array_shift($keys)]);
    }

    /**
     * Get an item from an array using "dot" notation.
     *
     * @param  array $array
     * @param  string $key
     * @param  mixed $default
     * @return mixed
     */
    static function array_get($array, $key, $default = null)
    {
        if (is_null($key)) return $array;
        if (isset($array[$key])) return $array[$key];
        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }
        return $array;
    }

    /**
     * Get a value from the array, and remove it.
     *
     * @param  array $array
     * @param  string $key
     * @param  mixed $default
     * @return mixed
     */
    static function array_pull(&$array, $key, $default = null)
    {
        $value = array_get($array, $key, $default);
        array_forget($array, $key);
        return $value;
    }

    /**
     * Determine if a given string ends with a given substring.
     *
     * @param string $haystack
     * @param string|array $needles
     * @return bool
     */
    static function end_with($haystack, $needles)
    {
        foreach ((array)$needles as $needle) {
            if ($needle == substr($haystack, -strlen($needle))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if a given string starts with a given substring.
     *
     * @param  string $haystack
     * @param  string|array $needles
     * @return bool
     */
    static function start_with($haystack, $needles)
    {
        foreach ((array)$needles as $needle) {
            if ($needle != '' && strpos($haystack, $needle) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if a given string contains a given substring.
     *
     * @param  string $haystack
     * @param  string|array $needles
     * @return bool
     */
    static function str_contains($haystack, $needles)
    {
        foreach ((array)$needles as $needle) {
            if ($needle != '' && strpos($haystack, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Generate a "random" alpha-numeric string.
     *
     * Should not be considered sufficient for cryptography, etc.
     *
     * @param int $length
     * @return string
     * @throws Exception
     */
    static function str_random($length = 16)
    {
        if (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes($length * 2);
            if ($bytes === false) {
                throw new LdException('Unable to generate random string.');
            }
            return substr(str_replace(array('/', '+', '='), '', base64_encode($bytes)), 0, $length);
        }
        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return substr(str_shuffle(str_repeat($pool, 5)), 0, $length);
    }


    /**
     * 将数据库查询到的数据拆分成key value对的形式
     * @param $arr
     * @param string $key
     * @return array
     */
    static function array2KeyValue($arr, $key = 'id', $override = true)
    {
        $result = array();
        foreach ($arr as $value) {
            if (empty($value[$key])) {
                $result = array();
                break;
            }
            $realKey = $value[$key];
            if ($override) {
                $result[$realKey] = $value;
            } else {
                $result[$realKey][] = $value;
            }

        }
        return $result;
    }


    /**
     * 取得数据库返回数据的某个字段
     * @param $arr
     * @param string $key
     * @return array
     */
    static function array2ValueList($arr, $key = 'id')
    {
        $result = array();
        foreach ($arr as $value) {
            if (empty($value[$key])) {
                continue;
            }
            $result[] = $value[$key];
        }
        return $result;
    }


    /**
     * 获取分页信息
     * @param $start
     * @param $count
     * @param int $pageCount
     * @param int $totalDisplayPageNumber
     * @return array
     */
    static function getPageInfo($start, $count, $pageCount = 10, $totalDisplayPageNumber = 6)
    {
        if (empty($count) || $count <= 0)
            return array('cur' => 0, 'start' => 0, 'pageCount' => $pageCount, 'limit' => array(0, $pageCount),
                'page' => 0, 'pageStr' => array(), 'startNum' => array(), 'count' => 0);
        $start = intval($start);
        $count = intval($count);
        # 计算总页数
        $page = 1;
        if ($pageCount > 0) {

            $page = intval(floor($count / $pageCount) + ($count % $pageCount == 0 ? 0 : 1));
        }

        if ($start < 0) $start = 0;
        $currentPage = 1;

        if ($pageCount > 0) $currentPage = intval(floor($start / $pageCount) + 1);
        if ($currentPage > $page) {
            $currentPage = $page;
            $start = ($currentPage - 1) * $pageCount;
            if ($start < 0) $start = 0;

        }

        $pageStr = array();
        // 存放翻页的start数据
        $startNumber = array();
        // 中间标尺部分页码的开始和结束下标（1,2...4,5,6,7,8,...10,11）,$startIndex为4,$endIndex为8
        $startIndex = $currentPage - intval(floor($totalDisplayPageNumber / 2));
        $endIndex = $currentPage + intval(floor($totalDisplayPageNumber / 2));
        # 修正下标
        if ($startIndex < 1) $startIndex = 1;
        if ($endIndex > $page) $endIndex = $page;
        # 判断是否显示前页
        if ($currentPage > 1) {
            $pageStr[] = '上一页';
            $startNumber[] = ($currentPage - 2) * $pageCount;
        }

        # 如果startIndex小于3，则从第一页开始一直显示到currentPage页(默认预留1,2页的页码:1,2...4,5,6)
        if ($startIndex <= 3) {

            foreach (range(1, $currentPage) as $value) {
                $pageStr[] = $value;
                $startNumber[] = ($value - 1) * $pageCount;
            }

        } else {
            $pageStr[] = 1;
            $startNumber[] = 0;
            $pageStr[] = 2;
            $startNumber[] = $pageCount;
            $pageStr[] = '...';
            $startNumber[] = '...';
            foreach (range($startIndex, $currentPage) as $value) {
                $pageStr[] = $value;
                $startNumber[] = ($value - 1) * $pageCount;
            }

        }

        # 代码的原理与上面相同
        if ($endIndex > $page - 3) {
            if ($currentPage == $page) {
//            $pageStr[] = $page;
//            $startNumber[] = ($page - 1) * $pageCount;
            } else {
                foreach (range($currentPage + 1, $page) as $v) {
                    $pageStr[] = $v;
                    $startNumber[] = ($v - 1) * $pageCount;
                }
            }

        } else {
            foreach (range($currentPage + 1, $endIndex) as $v) {
                $pageStr[] = $v;
                $startNumber[] = ($v - 1) * $pageCount;

            }
            $pageStr[] = '...';
            $startNumber[] = '...';
            $pageStr[] = $page - 1;
            $startNumber[] = ($page - 2) * $pageCount;
            $pageStr[] = $page;
            $startNumber[] = ($page - 1) * $pageCount;
        }
        # 判断是否显示后页
        if ($currentPage < $page) {
            $pageStr[] = '下一页';
            $startNumber[] = $currentPage * $pageCount;

        }
        return array('cur' => $currentPage, 'start' => $start, 'pageCount' => $pageCount, 'limit' => array($start, $pageCount),
            'page' => $page, 'pageStr' => $pageStr, 'startNum' => $startNumber, 'count' => $count);
    }


    /**
     * 取得分页html
     * @param $pageInfo
     * @param string $url
     * @param bool $container
     * @return string
     */
    static function getBootstrapPageHtml($pageInfo, $url = null, $container = True)
    {
        $url = is_null($url) ? $_SERVER['REQUEST_URI'] : $url;
        $htmlStr = $container ? '<ul class="pagination" style="float: right;">' : '';
        if ($pageInfo && $pageInfo['page'] > 1) {
            $key = 0;
            foreach ($pageInfo['pageStr'] as $value) {
                if ($value == '...') {
                    $htmlStr .= '<li class="disabled"><a>...</a></li>';
                } else {
                    if ($pageInfo['cur'] == $value) {
                        $htmlStr .= sprintf("<li class='active'><a href='javascript:void(0);' style='z-index:0;'>%s</a></li>", $value);
                    } else {
                        $parr = [];
                        $urlArr = parse_url($url);
                        if (array_get($urlArr, 'query')) {
                            parse_str($urlArr['query'], $parr);
                        }
                        $parr['start'] = $pageInfo['startNum'][$key];
                        $count = count($parr);
                        $i = 0;
                        $res = '';
                        foreach ($parr as $k => $v) {
                            if ($i < $count - 1) {
                                $res .= $k . '=' . $v . '&';
                            } else {
                                $res .= $k . '=' . $v;
                            }
                            $i++;
                        }
                        $htmlStr .= sprintf("<li><a href='%s?%s'>%s</a></li>", $urlArr['path'], $res, $value);
                    }
                }
                $key += 1;
            }
            if ($container) $htmlStr .= '</ul>';
            return $htmlStr;
        }
    }


    /**
     * 获取PC端分页
     * @param $pageInfo
     * @param string $url
     * @param bool $container
     * @return string
     */
    static function getWebHtml($pageInfo, $action = 'turnPage', $pageCount = 10, $container = True)
    {
        $htmlStr = $container ? '<div class="page-action white clear">' : '';
        if ($pageInfo && $pageInfo['page'] > 1) {
            $key = 0;
            foreach ($pageInfo['pageStr'] as $value) {
                if ($value == '...') {
                    $htmlStr .= '<span>......</span>';
                } else {
                    $startNum = $pageInfo['startNum'][$key];
                    if ($pageInfo['cur'] == $value) {
                        $htmlStr .= sprintf("<a role='button' class='active' href='javascript:void(0);' style='z-index:0;'>%s</a>", $value);
                    } else {
                        $class = '';
                        if (strpos($value, '上一页')) $class = 'class="page-up"';
                        if (strpos($value, '下一页')) $class = 'class="page-down"';
                        $htmlStr .= sprintf("<a role='button' %s href='javascript:void(0);' onclick='%s(%s,%s)'>%s</a>", $class, $action, $startNum, $pageCount, $value);
                    }
                }
                $key += 1;
            }
            $htmlStr .= "<span style='margin-left:50px;'>共{$pageInfo['page']}页</span>";
        }
        if ($container) $htmlStr .= '</div>';
        return $htmlStr;
    }

    /**
     * 获取一天的边界
     * @param string $date
     * @param int $returnType 返回数组类型， 默认返回string, 1返回int
     * @return array
     */
    static function getDayBoundary($date = '', $returnType = 0)
    {
        if (empty($date))
            return array('dateBegin' => '', 'dateEnd' => '');
        $dateSubStr = substr($date, 0, 10);
        $dateBegin = $dateSubStr . ' 00:00:00';
        $dateEnd = $dateSubStr . ' 23:59:59';
        if ($returnType == 0) {
            return array('dateBegin' => $dateBegin, 'dateEnd' => $dateEnd);
        } else {
            return array('dateBegin' => strtotime($dateBegin), 'dateEnd' => strtotime($dateEnd));
        }

    }


    /**
     * 工作日和周末计算
     * @param $dateStart
     * @param $dateEnd
     * @return array
     */
    static function countWorkdayWeekend($dateStart, $dateEnd)
    {
        $workday = 0;
        $weekend = 0;
        if (empty($dateStart) || empty($dateEnd)) return array('success' => false, 'workday' => $workday, 'weekend' => $weekend);
        $dateStartInt = strtotime($dateStart);
        $dateEndInt = strtotime($dateEnd);
        if ($dateStartInt > $dateEndInt) return array('success' => false, 'workday' => $workday, 'weekend' => $weekend);
        for (; $dateStartInt <= $dateEndInt; $dateStartInt += 24 * 60 * 60) {
            if (date('w', $dateStartInt) == 0 || date('w', $dateStartInt) == 6) {
                $weekend++;
            } else {
                $workday++;
            }
        }
        return array('success' => true, 'workday' => $workday, 'weekend' => $weekend);
    }


    /**
     * 获取一天的边界
     * @param int $curTime
     * @param int $inc 1明天便捷 -1昨天的边界
     * @return array
     */
    static function dayBoundary($curTime = 0, $inc = 0)
    {
        if (empty($curTime)) $curTime = time();
        $curDate = date('Y-m-d', $curTime);
        $timeStart = strtotime($curDate . ' 00:00:00');
        $timeEnd = strtotime($curDate . ' 23:59:59');
        if ($inc != 0) {
            $timeStart += $inc * 24 * 60 * 60;
            $timeEnd += $inc * 24 * 60 * 60;
        }
        return [$timeStart, $timeEnd];
    }

    /**
     * 获取兴趣的边界
     * @param int $curTime
     * @param int $inc
     * @return array
     */
    static function weekBoundary($curTime = 0, $inc = 0)
    {
        if (empty($curTime)) $curTime = time();
        $weekday = intval(date('w'));
        if ($weekday == 0) $weekday = 7;
        $start = $curTime - ($weekday - 1) * 24 * 60 * 60;
        $end = $curTime + (7 - $weekday) * 24 * 60 * 60;
        $timeStart = strtotime(date('Y-m-d', $start) . ' 00:00:00');
        $timeEnd = strtotime(date('Y-m-d', $end) . ' 23:59:59');
        if ($inc != 0) {
            $timeStart += $inc * 7 * 24 * 60 * 60;
            $timeEnd += $inc * 7 * 24 * 60 * 60;
        }
        return [$timeStart, $timeEnd];
    }


    /**
     * 获取周末的边界
     * @param int $curTime
     * @param int $inc
     * @return array
     */
    static function weekendBoundary($curTime = 0, $inc = 0)
    {
        if (empty($curTime)) $curTime = time();
        $weekday = intval(date('w'));
        if ($weekday == 0) $weekday = 7;
        if ($weekday < 6) {
            $start = $curTime + (6 - $weekday) * 24 * 60 * 60;
            $end = $curTime + (7 - $weekday) * 24 * 60 * 60;
        } else {
            $start = $curTime - ($weekday - 6) * 24 * 60 * 60;
            $end = $curTime + (7 - $weekday) * 24 * 60 * 60;
        }
        $timeStart = strtotime(date('Y-m-d', $start) . ' 00:00:00');
        $timeEnd = strtotime(date('Y-m-d', $end) . ' 23:59:59');
        if ($inc != 0) {
            $timeStart += $inc * 7 * 24 * 60 * 60;
            $timeEnd += $inc * 7 * 24 * 60 * 60;
        }
        return [$timeStart, $timeEnd];
    }

    /**
     * 月份的边界
     * @param int $curTime
     * @return array
     */
    static function monthBoundary($curTime = 0)
    {
        if (empty($curTime)) $curTime = time();
        $timeStart = mktime(0, 0, 0, date("m", $curTime), 1, date("Y", $curTime));
        $timeEnd = mktime(23, 59, 59, date("m", $curTime), date("t", $curTime), date("Y", $curTime));
        return [$timeStart, $timeEnd];
    }


    /**
     * 计算两点间的距离
     * @param $lng1
     * @param $lat1
     * @param $lng2
     * @param $lat2
     * @return int
     */
    static function getDistance($lng1, $lat1, $lng2, $lat2)
    {
        //将角度转为狐度
        $radLat1 = deg2rad($lat1);//deg2rad()函数将角度转换为弧度
        $radLat2 = deg2rad($lat2);
        $radLng1 = deg2rad($lng1);
        $radLng2 = deg2rad($lng2);
        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;
        return $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * 6378.137 * 1000;
    }


    /**
     * 签名数据过滤
     * @param $para
     * @return array
     */
    static function paramsFilter($para)
    {
        $para_filter = array();
        while (list ($key, $val) = each($para)) {
            if ($key == "sign" || $key == "sign_type" || $val == "") continue;
            else    $para_filter[$key] = $para[$key];
        }
        return $para_filter;
    }

    /**
     * 对数组排序
     * @param $para 排序前的数组
     * return 排序后的数组
     */
    static function paramsSort($para)
    {
        ksort($para);
        reset($para);
        return $para;
    }


    /**
     * 链接拼接
     * @param $para
     * @return string
     */
    static function createLinkStr($para, $type = 'weixin')
    {
        $arg = "";
        while (list ($key, $val) = each($para)) {
            $arg .= $key . "=" . $val . "&";
        }
        //去掉最后一个&字符
        if ($type == Config::get('app.payTypeWeixin')) $arg = trim($arg, "&");
        if ($type == Config::get('app.payTypeAlipay')) {
            $arg = substr($arg, 0, count($arg) - 2);
            if (get_magic_quotes_gpc()) {
                $arg = stripslashes($arg);
            }
        }
        //如果存在转义字符，那么去掉转义
        return $arg;
    }


    /**
     * 签名
     * @param $paramStr
     * @param string $type
     * @return string
     */
    static function md5Param($paramStr, $type = 'weixin')
    {
        if (!in_array($type, array(Config::get('app.payTypeWeixin'), Config::get('app.payTypeAlipay')))) return '';
        switch ($type) {
            case Config::get('app.payTypeWeixin'):
                $sign = strtoupper(md5(trim($paramStr . '&key=' . Config::get('app.weixinKey'))));
                break;
            case Config::get('app.payTypeAlipay'):
                $sign = md5($paramStr . Config::get('app.alipaySecretKey'));
                break;
            default:
                $sign = "";
        }
        return $sign;

    }


    /**
     * RSA签名
     * @param $data
     * @param $private_key_path
     * @return string
     */
    static function rsaSign($data, $private_key_path)
    {
        $priKey = file_get_contents($private_key_path);
        $res = openssl_get_privatekey($priKey);
        openssl_sign($data, $sign, $res);
        openssl_free_key($res);
        //base64编码
        $sign = base64_encode($sign);
        return $sign;
    }


    /**
     * 数组转xml
     * @param $params
     * @return string
     */
    static function arrayToXml($params)
    {
        if (!is_array($params) || count($params) <= 0) return '';

        $xml = "<xml>";
        foreach ($params as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }


    /**
     * xml转php数组
     * @param $xml
     * @return array|mixed
     */
    static function xmlToArray($xml)
    {
        if (!$xml) return array();
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $result = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $result;
    }


    /**
     * 微信签名验证
     * @param $param
     * @param $sign
     * @return bool
     */
    static function weixinSignVerify($param, $sign)
    {
        $postData = self::paramsFilter($param);
        $sortParams = self::paramsSort($postData);
        $urlParamStr = self::createLinkStr($sortParams);
        if ($sign != self::md5Param($urlParamStr)) return false;
        return true;

    }


    /**
     * 支付宝签名验证
     * @param $param
     * @param $sign
     * @return bool
     */
    static function alipaySignVerify($param, $sign)
    {
        $postData = self::paramsFilter($param);
        $sortParams = self::paramsSort($postData);
        $urlParamStr = self::createLinkStr($sortParams, 'alipay');
        if ($sign != self::md5Param($urlParamStr, 'alipay')) return false;
        return true;
    }

    /**
     * 签名验证
     * @param $data
     * @param $ali_public_key_path
     * @param $sign
     * @return bool
     */
    static function rsaVerify($data, $ali_public_key_path, $sign)
    {
        $postData = self::paramsFilter($data);
        $sortParams = self::paramsSort($postData);
        $urlParamStr = self::createLinkStr($sortParams, 'alipay');
        $pubKey = file_get_contents($ali_public_key_path);
        $res = openssl_get_publickey($pubKey);
        $result = (bool)openssl_verify($urlParamStr, base64_decode($sign), $res);
        openssl_free_key($res);
        return $result;
    }


    static function cookieDecrypt($encryptedText)
    {
        $cryptText = base64_decode($encryptedText);
        $ivSize = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($ivSize, MCRYPT_RAND);
        $decryptText = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, config('app.apisalt'), $cryptText, MCRYPT_MODE_ECB, $iv);
        return trim($decryptText);
    }


    /**
     * 加密cookie
     *
     * @param string $plainText
     * @return string
     */
    static function cookieEncrypt($plainText)
    {
        $ivSize = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($ivSize, MCRYPT_RAND);
        $encryptText = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, config('app.apisalt'), $plainText, MCRYPT_MODE_ECB, $iv);
        return trim(base64_encode($encryptText));
    }

    /**
     * 删除cookie
     *
     * @param array $args
     * @return boolean
     */
    static function delCookie($args)
    {
        $name = $args['name'];
        $domain = isset($args['domain']) ? $args['domain'] : null;
        return isset($_COOKIE[$name]) ? setcookie($name, '', time() - 86400, '/', $domain) : true;
    }

    /**
     * 得到指定cookie的值
     *
     * @param string $name
     */
    static function getCookie($name)
    {
        return isset($_COOKIE[$name]) ? self::cookieDecrypt($_COOKIE[$name]) : null;
    }

    /**
     * 设置cookie
     *
     * @param array $args
     * @return boolean
     */
    static function createCookie($args)
    {
        $name = $args['name'];
        $value = self::cookieEncrypt($args['value']);
        $expire = isset($args['expire']) ? $args['expire'] : time() + 24 * 60 * 60 * 7;
        $path = isset($args['path']) ? $args['path'] : '/';
        $domain = isset($args['domain']) ? $args['domain'] : null;
        $secure = isset($args['secure']) ? $args['secure'] : 0;
        return setcookie($name, $value, $expire, $path, $domain, $secure);
    }


    /**
     * 获取要设置cookie的域
     * @return string
     */
    static function cookieDomain()
    {
        $host = $_SERVER['HTTP_HOST'];
        $domainParams = explode('.', $host);
        if (count($domainParams) > 2) {
            $domain = '.' . implode('.', [$domainParams[count($domainParams) - 2], $domainParams[count($domainParams) - 1]]);
        } else {
            $domain = '.' . $host;
        }
        return $domain;
    }

    /**
     * 模块内部返回值
     * @param bool $success
     * @param string $msg
     * @param null $data
     * @return array
     */
    static function insideReturn($success = false, $msg = '', $data = null)
    {
        $returnData = array();
        if ($data && is_array($data)) {
            $returnData = $data;
        } else {
            $returnData['data'] = $data;
        }
        return array_merge(array('success' => $success, 'msg' => $msg), $returnData);
    }

    /**
     * 外部返回数据
     * @param string $status
     * @param string $msg
     * @param null $data
     * @return mixed
     */
    static function outsideReturn($status = 'alert', $msg = '', $data = null)
    {
        $returnData = array();
        if ($data && is_array($data)) {
            $returnData = $data;
        } else {
            $returnData['data'] = $data;
        }
        return Response::json(array_merge(array('status' => $status, 'msg' => $msg), $returnData));
    }


    /**
     * api数据返回
     * @param string $status
     * @param string $msg
     * @param null $data
     * @return mixed
     */
    static function apiReturn($status = 0, $msg = '', $data = null)
    {
        $result = ['success' => $status, 'message' => $msg];
        if ($data) $result['data'] = $data;
        return Response::json($result);
    }
    
    /**
     * 名字正则表达式
     * @param int $minLen
     * @param int $maxLen
     * @param string $charset
     * @return string
     */
    static function nameRegex($minLen = 2, $maxLen = 20, $charset = 'ALL')
    {
        switch ($charset) {
            case 'EN':
                $match = '/^[_\w\d]{' . $minLen . ',' . $maxLen . '}$/iu';
                break;
            case 'CN':
                $match = '/^[_\x{4e00}-\x{9fa5}\d]{' . $minLen . ',' . $maxLen . '}$/iu';
                break;
            default:
                $match = '/^[_\w\d\x{4e00}-\x{9fa5}]{' . $minLen . ',' . $maxLen . '}$/iu';
        }
        return $match;
    }

    /**
     * 密码正则表达式
     * @param int $minLen
     * @param int $maxLen
     * @return string
     */
    static function pwdRegex($minLen = 6, $maxLen = 12)
    {
        return '/^[\\~!@#$%^&*()-_=+|{}\[\],.?\/:;\'\"\d\w]{' . $minLen . ',' . $maxLen . '}$/';
    }


    /**
     * 手机号正则
     * @param int $minLen
     * @param int $maxLen
     * @return string
     */
    static function mobileRegex()
    {
        return "/^13[0-9]{1}[0-9]{8}$|14[0-9]{1}[0-9]{8}$|15[0-9]{1}[0-9]{8}$|17[0-9]{1}[0-9]{8}$|18[0-9]{1}[0-9]{8}$/";
    }


    /**
     * 信息摔选
     * @param $msgObg
     * @param array $keys
     * @param array $keyStr
     * @return string
     */
    static function filterMsg($msgObg, $keys = [], $keyStr = [])
    {
        $rArr = [];
        foreach ($keys as $k => $key) {
            if ($msgObg->has($key)) $rArr[] = $keyStr[$k];
        }
        if (empty($rArr)) return '';
        return implode(',', $rArr);
    }

    /**
     * 将date的星期转成中文
     * @param int $w
     * @return mixed
     */
    static function weekToString($w = 0)
    {
        $map = ['天', '一', '二', '三', '四', '五', '六'];
        return $map[$w];
    }


    /**
     * 将date的apm转成中文
     * @param $a
     * @return mixed
     */
    static function apmToString($a)
    {
        $map = ['am' => '上午', 'pm' => '下午'];
        return $map[$a];
    }


    /**
     * 纬度的边界
     * @param $dist
     * @param $lat
     * @return array
     */
    static function findLatBoundary($dist, $lat)
    {
        $d = ($dist / 6371.01 * 2 * M_PI) * 360;
        $lat1 = $lat - $d;
        $lat2 = $lat + $d;
        return [$lat1, $lat2];
    }


    /**
     * @param $lat
     * @param $lon
     * @param $lat1
     * @param $lat2
     * @return array
     */
    static function findLonBoundary($lat, $lon, $lat1, $lat2)
    {
        $d = $lat - $lat1;

        $d1 = $d / cos(deg2rad($lat1));
        $d2 = $d / cos(deg2rad($lat2));

        $lon1 = min($lon - $d1, $lon - $d2);
        $lon2 = max($lon + $d1, $lon + $d2);
        return [$lon1, $lon2];
    }


    /**
     * 递归循环每一个数组
     * @param $child
     * @return mixed
     */
    static function loopChildren(&$child)
    {

        if (!is_array($child)) return $child;
        foreach ($child as $key => &$value) {
            if (is_object($value)) $value = json_decode(json_encode($value), true);
            if (is_array($value)) self::loopChildren($value);
            if (is_null($value)) $value = '';
        }
    }

    /**
     * 筛选结果
     * @param $data
     * @return mixed
     */
    static function filterResult($data)
    {

        if (is_object($data)) $data = json_decode(json_encode($data), true);
        if (!is_array($data)) return $data;
        return array_map(function ($v) {
            if (is_object($v)) $v = json_decode(json_encode($v), true);
            if (is_array($v)) self::loopChildren($v);
            if (is_null($v)) {
                return '';
            } else {
                return $v;
            }
        }, $data);
    }

    /**
     * 获取图片地址
     * @param $imageId
     * @return string
     */
    static function imagePath($imageId)
    {
        $path = Image::select('id', 'dstFolder')->find($imageId);
        if ($path) {
            $pathStr = '/' . $path->dstFolder;
        } else {
            $pathStr = '/img/web/default.png';
        }
        return $pathStr;
    }


    /**
     * 批量获取图片路径
     * @param $imageIds
     * @return array
     */
    static function imagePaths($imageIds)
    {
        $result = [];
        $paths = Image::select('id', 'dstFolder')->whereIn('id', $imageIds)->get()->toArray();
        $pathKv = self::array2KeyValue($paths);
        foreach ($imageIds as $imageId) {
            $result[$imageId] = array_get($pathKv, $imageId) && array_get($pathKv[$imageId], 'dstFolder') ? '/' . $pathKv[$imageId]['dstFolder'] : '/img/web/default.png';
        }
        return $result;
    }

    /**
     * 重写condition
     * @param array $limit
     * @param int $level
     * @param array $condition0
     * @param array $condition1
     * @param string $group
     * @param array $condition2
     * @param string $role
     * @return array
     */

    static function rewriteRoleLimit($level = 2, $condition0 = [], $condition1 = [], $group = [], $condition2 = [], $role = '', $limit = [])
    {
        $limit = $limit ? $limit : Config::get('app.roleLimit');
        if ($level >= 0 && $condition0) $limit['condition'] = $condition0;
        if ($level >= 1 && $condition1 && $group) {
            if (array_get($limit['groups'], $group))
                $limit['groups'][$group]['condition'] = $condition1;
            if ($level >= 2 && $condition2 && $role) {
                if (array_get($limit['groups'][$group]['roles'], $role)) {
                    $limit['groups'][$group]['roles'][$role]['condition'] = $condition2;
                }
            }
        }
        return $limit;
    }


    /**
     * 解析limit 新加了级联表 table 但只限于所有的条件在一个表中，如果条件跨表 依旧很蛋疼
     * @param $group
     * @param $role
     * @param array $params
     * @param int $level
     * @param array $limit
     * @return array
     */
    static function parseRoleLimit($group, $role, $params = [], $level = 2, $limit = [], $judgeAdmin = true, $table = '')
    {
        $result = [];
        $limit = $limit ? $limit : Config::get('app.roleLimit');
        $defaultCondition = array_get($limit, 'condition');
        if ($defaultCondition) {
            foreach ($defaultCondition as $item) {
                $table ? $result[$table . '.'. $item] = array_get($params, $item) : $result[$item] = array_get($params, $item);
            }
        }
        if ($judgeAdmin && self::isAdmin()) return $result;
        if ($level <= 0) return $result;
        //组层拆解
        $groupLimit = array_get($limit['groups'], $group);
        if ($groupLimit) {
            $groupCondition = array_get($groupLimit, 'condition');
            if ($groupCondition) {
                foreach ($groupCondition as $item) {
                    $table ? $result[$table . '.'. $item] = array_get($params, $item) : $result[$item] = array_get($params, $item);
                }
            }
            if ($level <= 1) return $result;
            //角色层拆解
            if (array_get($groupLimit, 'roles') && array_get($groupLimit['roles'], $role)) {
                $roleLimit = array_get($groupLimit['roles'], $role);
                if ($roleLimit) {
                    $roleCondition = array_get($roleLimit, 'condition');
                    if ($roleCondition) {
                        foreach ($roleCondition as $item) {
                            $table ? $result[$table . '.'. $item] = array_get($params, $item) : $result[$item] = array_get($params, $item);
                        }
                    }
                }
            }
        }
        return $result;
    }


    /**
     * 判断是否是admin
     * @return bool
     */
    static function isAdmin()
    {
        $adminUser = Session::get(Config::get('app.adminUser'));
        if (array_get($adminUser, 'nickname') == 'admin') return true;
        return false;
    }


    /**
     * 是否可以访问
     * @param $controller
     * @param $action
     * @return bool
     */
    static function can($controller, $action)
    {
        if (Helpers::isAdmin()) return true;
        $adminUser = Session::get(Config::get('app.adminUser'));
        $roleId = $adminUser['roleId'];
        $group = $adminUser['group'];
        $key = Config::get('app.redisPrefix') . $group . '-' . $roleId;
        if (Cache::has($key)) {
            $modules = Cache::get($key);
            foreach ($modules as $module) {
                if (array_get($module, 'children')) {
                    foreach ($module['children'] as $child) {
                        if (array_get($child, 'permissions')) {
                            foreach ($child['permissions'] as $permission) {
                                if ($permission['controller'] == $controller && $permission['action'] == $action) return true;
                            }
                        }
                    }
                }
            }
            return false;
        } else {
            $permission = Permission::where(['controller' => $controller, 'action' => $action, 'deleted' => 0])->first();
            if ($permission) {
                if (RolePermission::where(['permissionId' => $permission->id, 'roleId' => $roleId, 'deleted' => 0])->get()->toArray()) return true;
            }
        }
        return false;
    }


    /**
     * 获取当前所在的模块
     * @return array
     */
    static function getCurrentModule()
    {
        $currentModule = [];
        list($controller, $action) = explode('@', Route::currentRouteAction());
        $moduleInfo = Module::where(['nameEn'=>$controller, 'deleted'=>0])->first();
        if ($moduleInfo) $currentModule = $moduleInfo->toArray();
        return $currentModule;
    }

}
