<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\UseFavorite;
use App\Models\UserAppointment;
use App\Models\Barber;
use App\Models\BarberPhotos;
use App\Models\BarberServices;
use App\Models\BarberTestimonial;
use App\Models\BarberAvailability;


class BarberController extends Controller
{
    private $loggedUser;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->loggedUser = auth()->user();
    }

    /*public function createRandom()
    {
        $array = ['error' => ''];

        for($q=0;$q<15;$q++)
        {
            $names = ['Pedro', 'Joao', 'Jofran', 'Vinicius', 'Antonio', 'Miles', 'Cesar', 'Ale', 'Izabela'];
            $lastNames = ['Pinheiro', 'Pereira', 'Silva', 'Santos', 'Pinto', 'Lopes', 'Costa', 'Mendes', 'Faustino'];

            $servicos = ['Corte', 'Pintura', 'Aparação', 'Enfeite'];
            $servicos2 = ['Cabelo', 'Unha', 'Pernas', 'Sobrancelhas'];

            $depos = [
                'Este é depoiment criado por mim pois não tenho criatividade e para escrever algo bonito',
                'Este depoimento foi escrito por mim pois também tenho preguiça de ir ao Lorem Ipsun para oegar uma frase',
                'Este outro depoimento foi feito também por mim pois meu PC provavelmente iria travar ao abrir o Lorem Ipsun',
                'Este último depoimento foi feito rapidamente pois estou com fome e tem açaí na geladeira',
                'Este outro depoimento foi feito depois pois eu esqueci que eram 5 depoimentos ao invés de 4'
            ];

            $newBarber = new Barber();
            $newBarber->name = $names[rand(0, count($names)-1)].' '.$lastNames[rand(0, count($lastNames)-1)];
            $newBarber->avatar = rand(1, 4).'.png';
            $newBarber->stars = rand(1, 4).'.'.rand(0, 9);
            $newBarber->latitude = '-23.5'.rand(0,9).'30907';
            $newBarber->longitude = '-46.6'.rand(0,9).'82795';
            $newBarber->save();

            $ns = rand(3, 6);

            for($w=0;$w<4;$w++)
            {
                $newBarberPhotos = new BarberPhotos();
                $newBarberPhotos->id_barber = $newBarber->id;
                $newBarberPhotos->url = rand(1, 5).'.png';
                $newBarberPhotos->save();
            }

            for($w;$w<$ns;$w++)
            {
                $newBarberServices = new BarberServices();
                $newBarberServices->id_barber = $newBarber->id;
                $newBarberServices->name = $servicos[rand(0, count($servicos)-1)].' de '.$servicos2[rand(0, count($servicos2)-1)];
                $newBarberServices->price = rand(1,99).'.'.rand(0,100);
                $newBarberServices->save();
            }

            for($w=0;$w<3;$w++)
            {
                $newBarberTestimonials = new BarberTestimonial();
                $newBarberTestimonials->id_barber = $newBarber->id;
                $newBarberTestimonials->name = $names[rand(0, count($names)-1)].' '.$lastNames[rand(0, count($lastNames)-1)];
                $newBarberTestimonials->rate = rand(2,4).'.'.rand(0, 9);
                $newBarberTestimonials->body = $depos[rand(0, count($depos)-1)];
                $newBarberTestimonials->save();

            }

            for($e=0;$e<4;$e++)
            {
                $rAdd = rand(7, 10);
                $hours = [];

                for($r=0;$r<8;$r++)
                {
                    $time = $r+$rAdd;
                    if($time < 10)
                    {
                        $time = '0'.$time;
                    }

                    $hours[] = $time.':00';

                }

                $newBarberAvail = new BarberAvailability();
                $newBarberAvail->id_barber = $newBarber->id;
                $newBarberAvail->weekday = $e;
                $newBarberAvail->hours = implode(',', $hours);
                $newBarberAvail->save();
            }

        }

        return $array;


    }*/

    //AIzaSyDGfWSz3flIS0jRrUQAD3xjVMTW3CpI41k
    private function geoSearch($adress)
    {
        $key = env('MAPS_KEY', null);
        $adress = urlencode($adress);

        $url = 'https://maps.google.com/maps/api/geocode/json?adress='.$adress.'&key='.$key;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($ch);
        curl_close($ch);

        return json_decode($res, true);
    }

    public function list(Request $request)
    {
        $array = ['error' => ''];

        $lat = $request->input('lat');
        $lng = $request->input('lng');
        $city = $request->input('city');
        $offset = $request->input('offset');

        if(!$offset)
        {
            $offset = 0;
        }

        if(!empty($city))
        {
            $res = $this->geoSearch($city);

            if(count($res['results']) > 0)
            {
                $lat = $res['results'][0]['geometry']['location']['lat'];
                $lng = $res['results'][0]['geometry']['location']['lng'];
            }
        }elseif(!empty($lat) && !empty($lng))
        {
            $res = $this->geoSearch($lat.','.$lng);

            if(count($res['results']) > 0)
            {
                $city = $res['results'][0]['formatted_adress'];
            }
        }else{
            $lat = '-23.5630907';
            $lng = '-46.6682795';
            $city = 'São Paulo';
        }

        $barbers = Barber::select(Barber::raw('*, SQRT(
            POW(69.1 * (latitude - '.$lat.'), 2) +
            POW(69.1 * ('.$lng.' - longitude) * COS(latitude / 57.3), 2)) AS distance'))
            ->havingRaw('distance < ?', [10])
            ->offset($offset)
            ->limit(5)
            ->orderBy('distance', 'ASC')
            ->get();

        foreach($barbers as $bkey => $bvalue)
        {
            $barbers[$bkey]['avatar'] = url('media/avatars/'.$barbers[$bkey]['avatar']);
        }

        $array['data'] = $barbers;
        $array['loc'] = 'Belém';

        return $array;
    }

    public function one($id)
    {
        $array = ['error' => ''];

        $barber = Barber::find($id);

        if($barber)
        {
            $barber['avatar'] = url('media/avatars/'.$barber['avatar']);
            $barber['favorited'] = false;
            $barber['photos'] = [];
            $barber['services'] = [];
            $barber['testimonials'] = [];
            $barber['available'] = [];

            //Verificando favorito
            $cFavorited = UserFavorite::where('id_user', $this->loggedUser->id)->where('id_barber', $barber->id)->count();

            if($cFavorited > 0)
            {
                $barber['favorited'] = true;
            }

            //Pegando as fotos do barbeiro
            $barber['photos'] = BarberPhotos::select(['id', 'url'])->where('id_barber', $barber->id)->get();
            foreach($barber['photos'] as $bpkey => $bpvalue){
                $barber['photos'][$bpkey]['url'] = url('media/avatars/'. $barber['photos'][$bpkey]['url']);

            }

            //Pegando os serviços do barbeiro
            $barber['services'] = BarberServices::select(['id', 'name', 'price'])->where('id_barber', $barber->id)->get();

            //Pegando os depoimentos do Barbeiro
            $barber['testimonials'] = BarberTestimonial::select(['id', 'name', 'rate', 'body'])->where('id_barber', $barber->id)->get();

            //Pegando disponilibidade do barbeiro
            $availability = [];

            //Pegando a disponilibidade crua
            $avails = BarberAvailability::where('id_barber', $barber->id)->get();
            $availWeekday = [];
            foreach($avails as $item)
            {
                $availWeekday[$item['weekday']] = explode(',', $item['hours']);
            }

            //Pegar o agendamento dos próximos 20 dias
            $appointments = [];
            $appQuery = UserAppointment::where('id_barber', $barber->id)
                ->whereBetween('ap_datetime', [
                    date('Y-m-d').'00:00:00',
                    date('Y-m-d', strtotime('+20 days')).'23:59:59'
                ])->get();

            foreach($appQuery as $appItem)
            {
                $appointments[] = $appItem['ap_datetime'];
            }

            //Gerar disponilibidade geral
            for($q=0;$q<20;$q++)
            {
                $timeItem = strtotime('+'.$q.' days');
                $weekday = date('w', $timeItem);

                if(in_array($weekday, array_keys($availWeekday)))
                {
                    $hours = [];
                    $dayItem = date('Y-m-d', $timeItem);

                    foreach($availWeekday[$weekday] as $hourItem)
                    {
                        $dayFormated = $dayItem.' '.$hourItem.':00';

                        if(!in_array($dayFormated, $appointments))
                        {
                            $hours[] = $hourItem;
                        }

                        if(count($hours) > 0)
                        {
                            $availability[] = [
                                'date' => $dayItem,
                                'hours' => $hours
                            ];
                        }
                    }
                }
            }
            

            $barber['available'] = $availability;

            $array['data'] = $barber;

        }else{
            $array['error'] = 'Barbeiro não encontrado.';
            return $array;
        }



        return $array;

    }

    public function setAppointment($id, Request $request)
    {
        $array = ['error' => ''];
        $service = $request->input('service');
        $year = intval($request->input('year'));
        $month = intval($request->input('month'));
        $day = intval($request->input('day'));
        $hour = intval($request->input('hour'));

        $month = ($month < 10) ? '0'.$month : $month;
        $day = ($day < 10) ? '0'.$day : $day;
        $hour = ($hour < 10) ? '0'.$hour : $hour;

        //1. Verificar se o barbeiro existe e seu serviço também
        $barberservice = BarberServices::select()->where('id', $service)->where('id_barber', $id)->first();

        if($barberservice)
        {
            //2. Verificar se a data é real
            $apDate = $year.'-'.$month.'-'.$day.' '.$hour.':00:00';

            if(strtotime($apDate) > 0)
            {
                //3. Verificar se já existe agendamento nesse dia/hora
                $apps = UserAppointment::select()->where('id_barber', $id)->where('ap_datetime', $apDate)->count();

                if($apps === 0)
                {
                    //4. Verificar se o barbeiro atende nesta data/hora
                    $weekday = date('w', strtotime($apDate));
                    $avail = BarberAvailability::select()->where('id_barber', $id)->where('weekday', $weekday)->first();

                    if($avail)
                    {
                        //4.2 Verificar se o barbeiro atende nesta hora.
                        $hours = explode(',', $avail['hours']);

                        if(in_array($hour.':00', $hours))
                        {
                            //5 Fazer o agendamento
                            $newApp = new UserAppointment();
                            $newApp->id_user = $this->loggedUser->id;
                            $newApp->id_barber = $id;
                            $newApp->id_service = $service;
                            $newApp->ap_datetime = $apDate;
                            $newApp->save();

                        }else{
                            $array['error'] = 'Barbeiro não atende nesse horário.';
                        }

                    }else{
                        $array['error'] = 'Esse barbeiro já possui agendamento ou não antende nessa data.';
                    }
                }else{
                    $array['error'] = 'Esse barbeiro já possui agendamento nesse dia/horário';
                }

            }else{
                $array['error'] = 'Data incorreta!';
            }

        }else{
            $array['error'] = 'Serviço inexistente!';
        }

        return $array;
    }

    public function search(Request $request)
    {
        $array = ['error' => '', 'list' => []];

        $q = $request->input('q');

        if($q)
        {
            $barbers = Barber::select()->where('name', 'LIKE', '%'.$q.'%')->get();

            foreach($barbers as $bkey => $bvalue)
            {
                $barbers[$bkey]['avatar'] = url('media/avatars/'.$barbers[$bkey]['avatar']);
            }

            $array['list'] = $barbers;

        }else{
            $array['error'] = 'Digite algo para buscar.';

        }

        return $array;
    }
}
