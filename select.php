
<!doctype html>
<html>
  <head>
    <title>Подобрать телефон по параметрам<?= $cp; ?></title>
    <meta name="description" content="Подбор телефона или смартфона по параметрам из множества моделей и от ведущих производителей.<?= $cp; ?>">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link rel="stylesheet" href="/Styles.css" type="text/css">
    <link rel="shortcut icon" href="/Images/mc.ico">
  </head>
  <body class="bd">
<?php
    ins_header(0, 0, 0); /*ЗАГОЛОВОК*/
?>
    <div class="spliter">&nbsp;</div>
    <div id="filter_mdiv">
      <table id="main">
        <tr>
<?php
/* ЛЕВАЯ ПАНЕЛЬ ***************************************************************************************************************************/
?>
          <td aligt="right" class="filter_aside">
            <aside>
<?php
              filter($_POST, $region); /* Форма поиска */
?>
              <br><br>
              <div align="right" id="sb_news"></div>
              <br><br>
              <div align="right" id="sb_article"></div>
            </aside>
          </td>
<?php
/* СРЕДНЯЯ ПАНЕЛЬ *************************************************************************************************************************/
?>
          <td class="filter_conttd">
            <div class="content" style="min-height:2000px">
              <article>
                <h1 class="hed">Результат подбора по параметрам<?= $cp; ?></h1>
                <div class="tdiv">
                  &nbsp;&nbsp;&nbsp;Ниже представлен результат подбора мобильных устройств по параметрам.
                    Для выполнения нового подбора, задайте критерии подбора в форме слево и нажмите подобрать
                </div>
<?php
 if ($cur_page > 0) {
     $lim = ($cur_page-1)*20;
 } else {
     $lim = 0;
 }
 $limit = ' LIMIT '.$lim.', 20';
 $where = $prop;
 $sql = Connect();  /* соединение с БД */

//******* запрос выборки  *******/
 $query='SELECT SQL_CALC_FOUND_ROWS
                p.id,
                p.name AS ps_name,
                p.foto,
                p.text,
                p.href,
                p.href2,
                p.href3,
                SUM(rat.v1+rat.v2+rat.v3+rat.v4) AS sum,
                COUNT(rat.v1) AS num,
                p.new,
                DATEDIFF("'.date('Y-m-d').'", p.new) AS days
            FROM product AS p
            JOIN p_clause AS cl ON cl.id=p.cl_id '.$data['wprod'].'
            LEFT JOIN prating AS rat ON rat.ps = p.id '.$data['wsum'].'
           WHERE cl.ch_id IN ('.$data['ch_id'].') '.$data['pid'].'
           GROUP BY p.id ORDER BY p.new DESC, sum'.$limit;

 $link = @mysqli_query($sql, $query);
 $nr = @mysqli_num_rows($link);

 if ($nr > 0) {  /* если данные найдены */
     $lnk = @mysqli_query($sql, 'SELECT FOUND_ROWS()');
     $rows = mysqli_fetch_array($lnk);
     $rows = $rows[0];
     $pages = @ceil($rows/20);
     $pid = '';
     if ($cur_page == 0) $cur_page = 1;
     for ($i = 0; $i < $nr; $i++) {
         $p = mysqli_fetch_assoc($link);
         if ($pid) {
             $pid .= ',' . $p['id'];
         } else {
             $pid=$p['id'];
         }
         $mas[$i]['id'] = $p['id'];
         $mas[$i]['name'] = $p['ps_name'];
         $mas[$i]['foto'] = $p['foto'];
         $rat = CalcRating($p['num'],$p['sum']);   /* определяем рейтинг товара */
         $mas[$i]['rimg'] = $rat[1];
         $mas[$i]['rating'] = $rat[0];
         $mas[$i]['days'] = $p['days'];
         $mas[$i]['obzor'] = $p['href2'];
         $mas[$i]['video'] = $p['href3'];
         $mas[$i]['num'] = $p['num'];
     }
     mysqli_free_result($link); /* освобождаем память */

     // Выборка свойств
     $query = 'SELECT ps_id,
                      prop_id,
                      value
                 FROM prod_prop
                WHERE ps_id IN ('.$pid.') AND prop_id IN (1,2,4,6,13,15,28,29,53)
                ORDER BY ps_id, prop_id';

     $lnk = @mysqli_query($sql, $query);
     $row = @mysqli_num_rows($lnk);
     for ($i = 0; $i < $row; $i++) {
         $p = @mysqli_fetch_array($lnk);
         switch ($p['prop_id']) {
             case 53:
                 $prp[$p['ps_id']]['kval'] = $p['value'];
                 break;
             case 1:
                 $prp[$p['ps_id']]['gval'] = $p['value'].'&nbsp;мм';
                 break;
             case 2:
                 $prp[$p['ps_id']]['wval'] = $p['value'].' г';
                 break;
             case 6:
                 $prp[$p['ps_id']]['sval'] = $p['value'].' "';
                 break;
             case 4:
                 $prp[$p['ps_id']]['tval'] = $p['value'];
                 break;
             case 13:
                 $prp[$p['ps_id']]['sim'] = $p['value'];
                 break;
             case 15:
                 if ($p['value'] >= 1000) {
                     $prp[$p['ps_id']]['mem'] = $p['value']/1000;
                     $prp[$p['ps_id']]['mem'] .= ' ГБ';
                 } else {
                     $prp[$p['ps_id']]['mem'] = $p['value'].' МБ';
                 }
                 break;
             case 28:
                 $prp[$p['ps_id']]['butt'] = $p['value'];
                 break;
             case 29:
                 if ($prp[$p['ps_id']]['butt']) {
                     $prp[$p['ps_id']]['butt'] .= ', '.$p['value'].' мАч';
                 } else {
                     $prp[$p['ps_id']]['butt'] .= $p['value'] . ' мАч';
                 }
                 break;
         }
     }
     mysqli_free_result($lnk); /* освобождаем память */

     /* выборка новостей */
     $query = 'SELECT ps_id,
                      href,
                      name
                 FROM news
                WHERE ps_id IN ('.$pid.') AND status="A"';

     $tmp = mysqli_query($sql, $query);
     $row = mysqli_num_rows($tmp);
     if ($row > 0) {
         for ($i = 0; $i < $row; $i++) {
             $p = @mysqli_fetch_array($tmp);
             $news[$p['ps_id']]['href'] = $p['href'];
             $news[$p['ps_id']]['name'] = $p['name'];
         }
         mysqli_free_result($tmp); /* освобождаем память */
     } else $news = 0;

     if($region['cnt']>0){   /* определяем валюту */
         switch ($region['cnt']) {
             case 1:
                 $curr = ' AND s.currency=4';
                 break;
             case 2:
                 $curr = ' AND s.currency=3';
                 break;
             case 3:
                 $curr = ' AND s.currency=5';
                 break;
             default:
                 $curr = '';

         }
     } else $curr = '';

     /* Выборка магазинов */
     $query='SELECT p.id,
                    p.name AS pname,
                    p.sid,
                    p.pid,
                    p.price,
                    p.price/cur.curs AS usd,
                    p.picture,
                    p.rate,
                    p.rating,
                    cur.name AS cur,
                    s.sname,
                    s.delivery,
                    c.id AS c_id,
                    c.name,
                    c.cnt
               FROM prices AS p
               JOIN shops AS s ON s.id=p.sid AND s.status="A"'.$curr.'
               JOIN shop_region AS r ON r.sid = p.sid '.$reg.'
               JOIN city AS c ON c.id=r.rid '.$cnt.'
               JOIN currency AS cur ON cur.id = s.currency
              WHERE p.pid IN ('.$pid.')
               GROUP BY p.id
               ORDER BY p.pid, p.rate DESC, p.rating DESC, usd';

     $lnk = mysqli_query($sql, $query);
     $rw = mysqli_num_rows($lnk);
     $i = $k = $current_pid = 0;
     $stat = $min = $indx = array();

     for ($j = 0; $j < $rw; $j++) {
         $p = mysqli_fetch_array($lnk);
         $usd = round($p['usd'],0);
         $price = str_replace('.00', '', $p['price']).'&nbsp;'.$p['cur'];
         if ($min[$p['pid']]['usd'] > 0) { /* определяем минимальную цену для каждого товара */
             if ($usd < $min[$p['pid']]['usd']) {
                 $min[$p['pid']]['usd'] = $usd;
                 $min[$p['pid']]['price'] = $price;
             }
         } else {
             $min[$p['pid']]['usd'] = $usd;
             $min[$p['pid']]['price'] = $price;
         }
         $min[$p['pid']]['count']++; /* определяем кол-во предложений магазинов для каждого товара */
         if ($p['pid'] != $current_pid) {  /* при переходе к новому товару сбрасываем счетчик магазинов */
             $current_pid = $p['pid'];
             $k=0;
         } elseif ($k > 5) continue; /* оставляем только по 5 магазинов на каждый товар */
         $key = $p['sid'].$p['pid'];
         if (in_array($key,$ps)) {  /* если для данного магазина данный товар повторяется */
             $indx[$key]['count']++; /* кол-во одинаковых товаров в каждом магазине */
             $equal[$key.$indx[$key]['count']] = $p;
         } else { /* если для данного магазина товар уникален */
             $ps[$i] = $key; /* список уникальных товаров по каждому магазину (для исключения повторяющихся товаров разных цветов) */
             $indx[$key]['row'] = $i; /* запоминаем индекс строки для которой будет выполнена ротация повторяющихся товаров */
             $indx[$key]['count'] = 1;
             $k++; /* кол-во магазинов для каждого товара */
             $shop[$i]['id'] = $p['id'];
             $shop[$i]['sid'] = $p['sid'];
             $shop[$i]['pid'] = $p['pid'];
             $shop[$i]['pname'] = $p['pname'];
             $shop[$i]['pic'] = $p['picture'];
             $shop[$i]['sname'] = $p['sname'];
             $shop[$i]['price'] = str_replace('.00','',$p['price']).'&nbsp;'.$p['cur'];
             $shop[$i]['usd']=round($p['usd'],0);
             if ($p['delivery'] > 0) {
                 $shop[$i]['deliv'] = $p['delivery'].'&nbsp;'.$p['cur'];
             } else {
                 $shop[$i]['deliv'] = '';
             }
             $shop[$i]['city_id'] = $p['c_id'];
             $shop[$i]['city'] = $p['name'];
             $shop[$i]['cnt'] = $p['cnt'];
             $plist[$i]['rate'] = $p['rate'];
             $plist[$i]['rating'] = $p['rating'];
             $plist[$i]['num'] = $i;
             $stat[$i]['id'] = $p['id'];  /* список id для статистики */
             $stat[$i]['sid'] = $p['sid'];  /* список sid для статистики */
             $i++;
         }
     }
     mysqli_free_result($lnk); /* освобождаем память */

     /* ротация повторяющихся товаров */
     $i=0;
     foreach($indx as $key => $value) {
         if ($value['count'] > 1) { /* если у магазина есть повторяющиеся товары */
             $rnd = rand(1,$value['count']); /* выбираем товар случайным образом */
             $n = $value['row']; /* индекс строки массива $shop */
             if ($rnd > 1) { /* если не первый повторяющийся товар магазина (т.к. первый товар уже записан в массиве $shop, а все остальные повторения в $equal) */
                 if ($key == $equal[$key.$rnd]['sid'].$equal[$key.$rnd]['pid']) { /* если данные найдены, то перезаписываем 1-й товар на найденный (т.е. повторяющиеся товары будут чередоваться при обновлении страницы) */
                     $shop[$n]['id'] = $equal[$key.$rnd]['id'];
                     $shop[$n]['sid'] = $equal[$key.$rnd]['sid'];
                     $shop[$n]['pid'] = $equal[$key.$rnd]['pid'];
                     $shop[$n]['pname'] = $equal[$key.$rnd]['pname'];
                     $shop[$n]['pic'] = $equal[$key.$rnd]['picture'];
                     $shop[$n]['sname'] = $equal[$key.$rnd]['sname'];
                     $shop[$n]['price'] = str_replace('.00','',$equal[$key.$rnd]['price']).'&nbsp;'.$equal[$key.$rnd]['cur'];
                     $shop[$n]['usd'] = round($equal[$key.$rnd]['usd'],0);
                     $stat[$n]['id'] = $equal[$key.$rnd]['id'];  /* перезаписываем данные статистики $stat[$n]['id'] (в случае когда пользователь зашел на страницу первый раз) */
                     $stat2[$i]['id'] = $equal[$key.$rnd]['id']; /* сохраняем данные товара из ротации для обновления статистики (в случае когда статистика для данного товара уже сохранена ранее) */
                     $stat2[$i]['sid'] = $equal[$key.$rnd]['sid'];
                 }
             }else{
                 $stat2[$i]['id'] = $shop[$n]['id']; /* сохраняем данные товара из ротации для обновления статистики (в случае когда статистика для данного товара уже сохранена ранее) */
                 $stat2[$i]['sid'] = $shop[$n]['sid'];
             }
             $i++;
         }
     }

     echo '<div id="nav_u"></div>'; /* Навигация */
     $m = 0;
     $country = array("Россия", "Беларусь", "Украина");
     for ($i = 0; $i < $nr; $i++) {
       /*--- карточка товара ---*/
?>
                          <section>
                            <table cellpadding="0" class="pcard_t">
                              <tr>
                                <td>
                                  <img src="/Images/tab_tl.gif" width="4px" class="k_i21" alt="">
                                </td>
                                <td class="w100">
                                  <table class="ttl" cellpadding="0">
                                    <tr>
                                      <td class="card_fh37">
<?php
                                        echo '<b>'.str_replace(' ', '&nbsp;', $mas[$i]['name']).'</b>';
                                        if (abs($mas[$i]['days']) > 0 && $mas[$i]['days'] < 150) {
                                            echo '&nbsp;<b class="new">(new)</b>';
                                        }
?>
                                      </td>
                                      <td>
                                        <img src="/Images/tab_tr.gif" width="14px" class="k_i21" alt="">
                                      </td>
                                      <td class="k_mid"></td>
                                      <td>
                                        <img src="/Images/tab_hl.gif" width="14px" class="k_i21" alt="">
                                      </td>
                                      <td class="card_fh12">
                                        <a href="/compare" onclick="clr()" class="tab_rf">Сравнить</a>
                                      </td>
                                      <td class="k_cmp">
<?php
                                        if ($_COOKIE['compare'] != '' && $clear_cook != 1) {
                                            $cook = explode(",", $_COOKIE['compare']);
                                        }
                                        if ($cook) {
                                            if(in_array($mas[$i]['id'], $cook)) {
                                                $checked = 'checked';
                                            } else {
                                                $checked='';
                                            }
                                        } else $checked = '';
?>
                                        <input type="Checkbox" id="<?= $mas[$i]['id']; ?>" <?= $checked; ?> onclick="Update(this, 'compare')">
                                      </td>
                                    </tr>
                                  </table>
                                </td>
                                <td>
                                  <img src="/Images/tab_hr.gif" width="7px" class="k_i21" alt="">
                                </td>
                              </tr>
                              <tr>
                                <td>
                                  <img src="/Images/tab_ml.gif" width="4px" class="k_i140" alt="">
                                </td>
                                <td class="bgw100">
                                  <table class="ktab">
                                    <tr>
                                      <td class="card_img">
<?php
                                        $img_path = '/Images/Prod_Img/Small/'; /* фото */
                                        $n = 0; /* количество дополнительных фото (мах=4) */
                                        for ($k = 1; $k <= 4; $k++) {
                                            $src = @substr('Images/Prod_Img/'.$mas[$i]['foto'],0,-4).'_v'.$k.substr('Images/Prod_Img/'.$mas[$i]['foto'],-4);
                                            if(file_exists($src)) $n++;
                                        }
?>
                                        <img title="Увеличить" class="kimg" alt="<?= $mas[$i]['name']; ?>" src="<?= $img_path.$mas[$i]['foto'] ?>"
                                             onclick="popup_img('/Images/Prod_Img/<?= $mas[$i]['foto'].'\', \''.$mas[$i]['name'].'\', '.$n; ?>)">
                                        <div align="center">
                                          <span class="krf" onclick="prating(<?= $mas[$i]['id'].', \''.str_replace(' ', '_', strtolower($mas[$i]['name'])).'\''; ?>)">
                                            Написать&nbsp;отзыв
                                          </span>
                                        </div>
                                        <div class="kstar">
                                          <img src="/Images/<?= $mas[$i]['rimg']; ?>" alt="" title="Рейтинг: <?= $mas[$i]['rating']; ?>">
                                        </div>
                                      </td>
                                      <td class="card_prop">
                                        <div class="khed">Характеристики:</div>
<?php
                                        if ($prp[$mas[$i]['id']]['sim'] > 1) {
                                            $sim = $prp[$mas[$i]['id']]['sim'];
                                        } else {
                                            $sim = 1;
                                        } /* если числ SIM пусто, то записываем 1, т.к. записей с числом SIM=1 нет в БД */
?>
                                        <table class="kptab">
                                          <tr>
                                            <td>
                                              <span>Тип&nbsp;корпуса</span>
                                            </td>
                                            <td class="kpt_r">
                                              &nbsp;<span><?= $prp[$mas[$i]['id']]['kval']; ?></span>
                                            </td>
                                          </tr>
                                          <tr>
                                            <td>
                                              <span>Габариты</span>
                                            </td>
                                            <td class="kpt_r">
                                              &nbsp;<span><?= $prp[$mas[$i]['id']]['gval']; ?></span>
                                            </td>
                                          </tr>
                                          <tr>
                                            <td>
                                              <span>Вес</span>
                                            </td>
                                            <td class="kpt_r">
                                              <span><?= $prp[$mas[$i]['id']]['wval']; ?></span>
                                            </td>
                                          </tr>
                                          <tr>
                                            <td>
                                              <span>Экран</span>
                                            </td>
                                            <td class="kpt_r">
                                              <span><?= $prp[$mas[$i]['id']]['sval'].',&nbsp;'.@str_replace(' ', '&nbsp;', $prp[$mas[$i]['id']]['tval']) ?></span>
                                            </td>
                                          </tr>
                                          <tr>
                                            <td>
                                              <span>Число&nbsp;SIM-карт</span>
                                            </td>
                                            <td class="kpt_r">
                                              &nbsp;<span><?= $sim.'&nbsp;'; ?>SIM</span>
                                            </td>
                                          </tr>
                                          <tr>
                                            <td>
                                              <span>Память</span>
                                            </td>
                                            <td class="kpt_r">
                                              <span><?= $prp[$mas[$i]['id']]['mem']; ?></span>
                                            </td>
                                          </tr>
                                          <tr>
                                            <td>
                                              <span>Аккумулятор</span>
                                            </td>
                                            <td class="kpt_r">
                                              <span><?= $prp[$mas[$i]['id']]['butt']; ?></span>
                                            </td>
                                          </tr>
                                        </table>
                                      </td>
                                      <td class="card_shop">
                                          <div class="khed">Магазины:</div>
                                          <div>(
                                              <div class="reg">Регион:&nbsp;&nbsp;
                                                  <span onclick="viewRegions()"><?= $region['name']; ?></span>
                                              </div> )
                                          </div>
<?php
                                        $find=0;
                                        for ($j = 0; $j < $rw; $j++) {
                                            if ($mas[$i]['id'] == $shop[$j]['pid']) {
                                                $find++;
                                                if($find<=5){
?>
                                                    <div class="cust">
   	                                                  <div class="cnam">
<?php
                                                         if (in_array($region['name'], $country)) {
                                                             $title = 'title="Регион: '.$region['name'].'"';
                                                         } else {
                                                             $title = 'title="Регион: '.$shop[$j]['city'].'"';
                                                         }
?>
                                                        <img src="/Images/cnt_<?= $shop[$j]['cnt'].'.png'; ?>" alt="" <?= $title;?> >&nbsp;
                                                        <span class="rf" onclick = "gotoshop(<?= '\''.$shop[$j]['id'].'\', \'T\', '.$razdel.', '.$region['rid']; ?>)">
                                                            <?= $shop[$j]['sname']; ?>
                                                        </span>
                                                      </div>
                                                      <div class="price">
<?php
                                                         echo $shop[$j]['price'].'&nbsp;';
                                                         if ($shop[$j]['cur'] != "usd") {
                                                             echo $shop[$j]['cur'] . '&nbsp;<span>('.$shop[$j]['usd'].'$)</span>';
                                                         } else {
                                                             echo '$&nbsp;';
                                                         }
?>
                                                      </div>
                                                      <div class="kcb"></div>
                                                    </div>
<?php
                                                } else break;
                                            } else {
                                                if ($find > 0) break;
                                            }
                                        } /* конец цыкла */
                                        if ($find == 0) {   /* если нет продавцов */
                                            if ($news[$mas[$i]['id']]['href']) {
?>
                                                <div class="kns2">В данном регионе магазинов пока нет</div>
                                                <div class="knw">В новостях:
                                                    <div>
                                                        <a href="/newsreview/'.$news[$mas[$i]['id']]['href'].'" class="rf"><?= $news[$mas[$i]['id']]['name']; ?></a>
                                                    </div>
                                                </div>
<?php
                                            } else {
                                                echo '<div class="kns">В данном регионе магазинов пока нет</div>';
                                            }
                                        } else {
?>
                                            <div class="card_gk">
                                                <a href="/gde_kupit/<?= str_replace(' ', '_', strtolower($mas[$i]['name'])).'/'.$mas[$i]['id'] ?>" class="rf">
                                                    Все магазины (<?= $min[$mas[$i]['id']]['count'] ?>)
                                                </a>
                                            </div>
<?php
                                        }
?>
                                      </td>
                                    </tr>
                                  </table>
                                </td>
                                <td>
                                    <img src="/Images/tab_mr.gif" width="7" class="k_i140" alt="">
                                </td>
                              </tr>
                              <tr class="valign">
                                <td>
                                    <img src="/Images/tab_bl.gif" width="4" class="k_i21" alt="">
                                </td>
                                <td class="btn_line">
                                  <table class="card_ref" cellpadding="0">
                                    <tr>
<?php
                                      $name = str_replace(' ', '_', strtolower($mas[$i]['name']));
?>
                                      <td class="tab_fb">
                                          <a href="/property/<?= $name.'/'.$mas[$i]['id']; ?>" class="tab_rf">Параметры</a>
                                      </td>
                                      <td>
                                          <img class="sep" src="/Images/tab_sep.gif" alt="">
                                      </td>
                                      <td class="tab_fb">
                                          <a href="/opisanie/<?= $name.'/'.$mas[$i]['id']; ?>" class="tab_rf">Описание</a>
                                      </td>
                                      <td>
                                          <img class="sep" src="/Images/tab_sep.gif" alt="">
                                      </td>
                                      <td class="tab_fb">
<?php
                                        if ($mas[$i]['obzor']) {
                                            echo '<a href="/obzor/'.$name.'/'.$mas[$i]['id'].'" class="tab_rf">Обзор</a>';
                                        } else {
                                            echo '<span class="kna">Обзор</span>';
                                        }
?>
                                      </td>
                                      <td>
                                          <img class="sep" src="/Images/tab_sep.gif" alt="">
                                      </td>
                                      <td class="tab_fb">
<?php
                                        if ($mas[$i]['video']) {
                                            echo '<a href="/videoobzor/'.$name.'/'.$mas[$i]['id'].'" class="tab_rf">Видеообзор</a>';
                                        } else {
                                            echo '<span class="kna">Видеообзор</span>';
                                        }
?>
                                      </td>
                                      <td>
                                          <img class="sep" src="/Images/tab_sep.gif" alt="">
                                      </td>
                                      <td class="tab_fb">
<?php
                                        if ($mas[$i]['num']) {
                                            echo '<a href="/rating/'.$name.'/'.$mas[$i]['id'].'/1" class="tab_rf">Отзывы</a>&nbsp;<span class="krat">('.$mas[$i]['num'].')</span>';
                                        } else {
                                            echo '<span class="kna">Отзывы</span>';
                                        }
?>
                                      </td>
                                      <td>
                                          <img class="sep" src="/Images/tab_sep.gif" alt="">
                                      </td>
<?php
                                      $result = get_accessories($mas[$i]['id'], $mas[$i]['name'], $reg, $cnt);
                                      if ($result > 0) {
                                          switch ($result) {
                                              case 1:
                                                  $tab='chehol';
                                                  break;
   	                                          case 2:
                                                  $tab='plenka';
                                                  break;
   	                                          case 3:
                                                  $tab='akkumulyatory';
                                                  break;
   	                                          case 4:
                                                  $tab='derzhatel';
                                                  break;
                                              case 5:
                                                  $tab='garnitura';
                                                  break;
   	                                          case 6:
                                                  $tab='zaryadnoe';
                                                  break;
                                          }
?>
                                          <td class="tab_fb">
                                              <a href="/phone_accessories/'.$name.'/'.$mas[$i]['id'].'/'.$tab.'/aname/1" class="tab_rf">Аксессуары</a>
                                          </td>
                                          <td>
                                              <img src="/Images/tab_sep.gif" alt="">
                                          </td>
<?php
                                      } else {
?>
                                          <td class="tab_fb">
                                              <span class="kna">Аксессуары</span>
                                          </td>
                                          <td>
                                              <img src="/Images/tab_sep.gif" alt="">
                                          </td>
<?php
                                      }
?>
                                      <td class="tab_fb">
<?php
                                        if ($min[$mas[$i]['id']]['usd']) {
                                            if ($region['cnt'] > 0) {
                                                echo 'Цена:&nbsp;от&nbsp;<a href="/gde_kupit/'.$name.'/'.$mas[$i]['id'].'" class="pref">'
                                                     .$min[$mas[$i]['id']]['price'].'&nbsp;'.$min['cur'].'</a>';
                                            } else {
                                                echo 'Цена:&nbsp;от&nbsp;<a href="/gde_kupit/'.$name.'/'.$mas[$i]['id'].'" class="pref">'
                                                     .$min[$mas[$i]['id']]['usd'].'&nbsp;$</a>';
                                            }
                                        }
?>
                                      </td>
                                      <td>
                                        <img src="/Images/tab_bmm.gif" width="27px" height="21px" alt="">
                                      </td>
                                    </tr>
                                  </table>
                                </td>
                                <td>
                                  <img src="/Images/tab_br.gif" width="7px" class="k_i21" alt="">
                                </td>
                              </tr>
                            </table>
                          </section>
<?php
                      }
?>
                      <div id="nav_d"></div> <?php /* Навигация */ ?>
<?php
                  } else { /* если данные не найдены */
                      if ($data['wsum']) {
                          e_msg('Результат поиска', '/Images/info.gif', 'Для региона &quot;'.$region['name'].'&quot; нет данных!', 'Измените параметры подбора.');
                      } else {
                          e_msg('Результат поиска', '/Images/info.gif', 'Данные не найдены!', 'Измените параметры подбора.');
                      }
                  }
?>
                </article><br>
<?php
                /*** Предложения магазинов ***/
                $r = @count($plist);
                if($r1 > 0){ /* если данные найдены */   /* временно установлена заглушка $r1, чтобы отражались предложения mobiguru, т.к. они пока более выгодны */
                    rsort($plist); /* сортировка по убыванию ставки */
                    for ($i = 0; $i < $r; $i++) {
                        $j = $plist[$i]['num'];
                        $shop2[$i] = $shop[$j];
                        if ($slist) {
                            $slist .= ','.$shop[$j]['sid'];
                        } else {
                            $slist = $shop[$j]['sid'];
                        }
                        if ($i >= 4) break;
                    }
                    $rating = GetShopsRating($slist);
                    display_price_d($shop2, $rating, $razdel, $region);
                } else {
?>
                    <fieldset class="ads">
                        <legend>Другие предложения магазинов</legend>
                        <div id="mobiguru" class="mguru">Нет</div>
                    </fieldset>
<?php
                }
                code_ads_mguru_m();
?>
              </div>
              <div id="filter_result" onclick="filter()"></div>
            </td>
          <td width="7px"></td>
        </tr>
      </table>
    </div>
    <footer>
        <script src="/JS/Scripts.js"></script>
        <script src="/JS/jquery.js"></script>
        <script>
            sidebar();
            getnav(<?= "'select', '', '', $cur_page, $pages, 0, 0";?>);
        </script>
<?php
        Zoom_foto();
        counters();
        ads_mguru_script("", "");
        footer();
?>
        <script>sh();</script>
    </footer>
  </body>
</html>
