<?php 
    function showsp($mangsp, $col = null, $advl = '') {
        $chuoisp = "";
        $colums = ($col) ? $col : "col-3";
        foreach ($mangsp as $value => $item) {
            $pfn = caldate($item['f_date'],$item['t_date'],$item['price'],$item['sale']);
            if ($pfn['is_sale']) $price = "<span>".gennum($pfn['old'])."</span>".gennum($pfn['pfn']);
            else { $price = gennum($pfn['pfn']);}
            $chuoisp.= "
            <div class=\"$colums text-center $advl\">
                <div class=\"khungsp\">
                    <div class=\"khungxam nav-item\">
                        <a href=\"".genurl($item['id'],'detail')."\" class=\"nav-link\">
                            <img src=\"".genurl($item['img'])."\" class=\"anhsp nav-link\" alt=\"\">
                        </a>                               
                    </div>                                
                    <p class=\"tt tensp\">".$item['name']."</p>
                    <p class=\"tt giasp\">".$price."</p>
                    <div class=\"nut_sp\">
                        <a href=\"".genurl($item['id'],'cart/buy')."\" class=\"btn nutsp\">Mua Ngay</a>
                        <button class=\"btn nutsp addcart\" data-idsp=\"".$item['id']."\"><i class=\"fa-solid fa-cart-plus\"></i></button>
                    </div>
                </div>                    
            </div>";
        }
        return $chuoisp;
    }
    function showsp2($mangsp, $col = null, $advl = '') {
        $chuoisp = "";
        $colums = ($col) ? $col : "col-3";
        foreach ($mangsp as $value => $item) {
            $pfn = caldate($item['f_date'],$item['t_date'],$item['price'],$item['sale']);
            if ($pfn['is_sale']) $price = "<span>".gennum($pfn['old'])."</span>".gennum($pfn['pfn']);
            else { $price = gennum($pfn['pfn']);}
            $chuoisp.= "
            <div class=\"$colums text-center $advl\">
                <div class=\"khungsp2\">
                    <div class=\"khungxam position-relative nav-item\">
                        <a href=\"".genurl($item['id'],'detail')."\" class=\"nav-link\">
                            <img src=\"".genurl($item['img'])."\" class=\"anhsp nav-link\" alt=\"\">
                        </a>
                        <p class=\"tt vnssp\">
                            <i class=\"fa-solid fa-eye\"></i> ".$item['viewed']."<br>
                            <i class=\"fa-solid fa-cart-flatbed\"></i> ".$item['saled']."
                        </p>                               
                    </div>                                
                    <p class=\"tt tensp\">".$item['name']."</p>
                    <p class=\"tt giasp\">".$price."</p>
                    <div class=\"nut_sp\">
                        <a href=\"".genurl($item['id'],'cart/buy')."\" class=\"btn nutsp\">Mua Ngay</a>
                        <button class=\"btn nutsp addcart\" data-idsp=\"".$item['id']."\"><i class=\"fa-solid fa-cart-plus\"></i></button>
                    </div>
                </div>                    
            </div>";
        }
        return $chuoisp;
    }
    function gennum($number) {
        if ($number == null) $number = 0;
        return Number::format($number, locale: 'vi');
    }
    function genurl($url,$type='data') {
        return asset("$type/$url");
    }
    function issale($prod) {
        $now = new DateTime();
        $f_date = new DateTime($prod->f_date);
        $t_date = new DateTime($prod->t_date);
        if ($f_date > $now || $t_date < $now) return false;
        else return true;
    }
    function gendate($date) {
        return date("d-m-Y",strtotime($date));
    }
    function caldate($from,$to,$price,$sale=0) {
        $result['is_sale'] = false;
        if ($sale == 0) $result['pfn'] = $price;
        else {
            $now = new DateTime();
            $fd = new DateTime($from);
            $td = new DateTime($to);
            if ($fd > $now || $td < $now) $result['pfn'] = $price;
            else {
                $result['is_sale'] = true;
                $result['pfn'] = $sale;
                $result['old'] = $price;
            }
        } 
        return $result;
    }
    function dumpdt($data) {
        echo "<pre>";
        var_dump($data);
        echo "</pre>";
    }
?>  
    