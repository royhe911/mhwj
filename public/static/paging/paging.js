$(function() {
    var $div = $(".paging_page");
    var $page = 1;
    var $url = location.href;
    /*取得整个URL*/
    var $index = $url.indexOf('?');
    var $params = '';
    if ($index > 0) {
        var str = $url.substr($index + 1);
        var arr = str.split("&");
        /*各个参数放到数组里*/
        for (var $i = 0; $i < arr.length; $i++) {
            var $num = arr[$i].indexOf('=');
            if ($num > 0) {
                if (arr[$i].substr(0, $num) == 'page') {
                    $page = parseInt(arr[$i].substr($num + 1));
                } else {
                    $params += ('&' + arr[$i]);
                }
            }
        }
    }
    var $count = parseInt($div.html());
    if ($count > 0) {
        $div.html('');
        var $index = 1;
        if ($page > 3) {
            $index = $page - 2;
            $div.html('<a href="?page=1' + $params + '">首页</a>');
        }
        if ($page > 1) {
            $div.html($div.html() + '<a href="?page=' + ($page - 1) + $params + '">上一页</a>');
        } else {
            $div.html($div.html() + '<span style="cursor:not-allowed">上一页</span>');
        }
        var $last = $count;
        if ($page + 3 <= $count) {
            $last = $page + 2;
        }
        for (var $i = $index; $i <= $last; $i++) {
            if ($i == $page) {
                $div.html($div.html() + '<span class="current">' + $page + '</span>');
            } else {
                $div.html($div.html() + '<a href="?page=' + $i + $params + '">' + $i + '</a>');
            }
        }
        if ($page < $count) {
            $div.html($div.html() + '<a href="?page=' + (parseInt($page) + 1) + $params + '">下一页</a>');
        } else {
            $div.html($div.html() + '<span style="cursor:not-allowed">下一页</span>');
        }
        if ($page + 3 <= $count) {
            $div.html($div.html() + '<a href="?page=' + $count + $params + '">尾页</a>');
        }
    }
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
});