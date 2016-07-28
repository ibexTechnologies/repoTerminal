<?php defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH.'/libraries/REST_Controller.php';

require_once __DIR__.'/lib/autoloader.php';

require_once __DIR__.'/lib/autoload.php';

use Pubnub\Pubnub;

use Parse\ParseObject;
use Parse\ParseQuery;
use Parse\ParseACL;
use Parse\ParsePush;
use Parse\ParseUser;
use Parse\ParseInstallation;
use Parse\ParseException;
use Parse\ParseAnalytics;
use Parse\ParseFile;
use Parse\ParseCloud;
use Parse\ParseClient;

class Comentarios extends REST_Controller
{
	var $libraries = array('encrypt', 'utilidades');
    var $models = array('report_model', 'user_model');

	function __construct() {
		parent::__construct();

		$this->load->model('m_comentarios', 'comentarios');
        $this->load->model('m_reportes', 'reportes');
        $this->load->model('report_model');
		$this->load->library($this->libraries);
	}

	function obtener_post() {
		$result = array();
		$reporte = $this->post('reporte');

		if ($reporte == null) {
			$result['codigo']	= 0;
			$result['mensaje']	= 'Falta entrega de parametros.';
		}
		else {
			$comentarios = array();
			$query = $this->comentarios->obtener($reporte);

			if ($query != false) {
				foreach ($query as $row) {
					$data['usuario_id']	= $row->usr_id;
					$data['usuario']	= $row->usr_username;
					$data['email']		= $row->usr_email;
					$data['avatar']		= ($row->usr_avatar == 'default_avatar.jpg')?base_url('graficas/'.$row->usr_avatar):base_url('files/'.$row->usr_id.'/'.$row->usr_avatar);
					$data['id']			= $row->ucr_id;
					$data['fecha']		= $this->utilidades->formato_fecha($row->ucr_creation_date);
					$data['comentario']	= $row->ucr_comment;

					array_push($comentarios, $data);
				}

				$result['codigo']		= 1;
				$result['mensaje']		= 'Comentarios desplegados con exito.';
				$result['comentarios']	= $comentarios;
			}
			else {
				$result['codigo']		= 0;
				$result['mensaje']		= 'El reporte no tiene comentarios.';
			}
		}

		$this->response($result, 200);
	}

	function test_post() {
		$result = array();
		$reporte = $this->post('reporte');

		$this->response($this->comentarios->obtener($reporte), 200);
	}

    public function sendEmail($sender_email = null,$sender_name = null, $content, $email_to){
        $this->load->library('email');
        $config['protocol'] = 'smtp';
        $config['smtp_host'] = 'ssl://smtp.gmail.com';
        $config['smtp_port'] = '465';
        $config['smtp_user'] = 'notificaciones@wof.cl';
        $config['smtp_pass'] = 'wof.2015';

        $config['mailpath'] = '/usr/sbin/sendmail';
        $config['charset'] = 'utf-8';
      
        $config['mailtype'] = "html";
        $config['newline'] = "\r\n";
        
        $this->email->initialize($config);
        
        $this->email->from('notificaciones@wof.cl', 'WOF');
        $this->email->to($email_to); 
        if ($sender_email != null && $sender_name != null){
            $this->email->reply_to($sender_email, $sender_name);
        }


        $this->email->subject('Wof App - ' . $sender_name . ' ha comentado tu reporte' );
        $this->email->message($content);	

        if (!$this->email->send()){
            return false;
        }
        else {
            return true;
        }
        //echo $this->email->print_debugger();
    }

    function crear_post() {
    	$result = array();
    	$usuario = $this->post('usuario');
    	$reporte = $this->post('reporte');
    	$comentario = $this->post('comentario');

    	if ($usuario == null or $reporte == null or $comentario == null) {
    		$result['codigo']	= 0;
    		$result['mensaje']	= 'Falta entrega de parametros.';
    	}
    	else {
    		$query = $this->comentarios->crear($usuario, $reporte, $comentario);

    		if ($query != false) {
    			$data['usuario_id']	= $query->usr_id;
    			$data['usuario']	= $query->usr_username;
    			$data['email']		= $query->usr_email;
    			$data['avatar']		= ($query->usr_avatar == 'default_avatar.jpg')?base_url('graficas/'.$query->usr_avatar):base_url('files/'.$query->usr_id.'/'.$query->usr_avatar);
    			$data['id']			= $query->ucr_id;
    			$data['fecha']		= $this->utilidades->formato_fecha($query->ucr_creation_date);
    			$data['comentario']	= $query->ucr_comment;

    			$usuario_reporte = $this->comentarios->obtenerPorReporte($reporte);
    			$email_destinatario	= $usuario_reporte->usr_email;
    			$nombre_destinatario = $usuario_reporte->usr_username;
    			$nombre_comentarista = $data['usuario'];
    			$descripcion_reporte = $usuario_reporte->repo_comment;
                $tipo_reporte = $usuario_reporte->repo_type;
                $id_destinatario = $usuario_reporte->usr_id;
                $notificacion = $usuario_reporte->usr_notification;
                $push_comentario = $usuario_reporte->notification_comment;
                //$push_comentario = 1;

                //obener reporte completo
                $distancia = $this->utilidades->distancia(0, 0, $usuario_reporte->repo_address_latitude, $usuario_reporte->repo_address_longitude);
                $data_report['id']                 = $usuario_reporte->uhr_user;
                $data_report['nombre']             = $usuario_reporte->usr_name;
                $data_report['usuario']            = $usuario_reporte->usr_username;
                $data_report['avatar']             = ($usuario_reporte->usr_avatar == 'default_avatar.jpg')?base_url('graficas/'.$usuario_reporte->usr_avatar):base_url('files/'.$usuario_reporte->uhr_user.'/'.$usuario_reporte->usr_avatar);
                $data_report['propietario']        = $usuario_reporte->uhr_user == true;
                $data_report['reporte_id']         = $usuario_reporte->repo_id;
                $data_report['reporte_tipo']       = $usuario_reporte->repo_type;
                $data_report['reporte_cerrado']    = ($usuario_reporte->repo_closed != false)?true:false;
                $data_report['reporte_imagen']     = (!empty($usuario_reporte->repo_image))?base_url('files/'.$usuario_reporte->uhr_user.'/'.$usuario_reporte->repo_id.'/'.$usuario_reporte->repo_image):base_url('graficas/default_report.jpg');
                $data_report['reporte_distancia']  = "0";
                $data_report['reporte_comentario'] = $usuario_reporte->repo_comment;
                $data_report['reporte_fecha']      = $this->utilidades->formato_fecha($usuario_reporte->repo_create);
                $data_report['reporte_direccion']  = $usuario_reporte->repo_address_words;
                //$data_report['reporte_termino']    = $usuario_reporte->fecha_termino;
                $data_report['reporte_latitud']    = $usuario_reporte->repo_address_latitude;
                $data_report['reporte_longitud']   = $usuario_reporte->repo_address_longitude;
                $data_report['seguidores']         = $this->reportes->obtenerSeguidores($usuario_reporte->repo_id, 'muro');
                $data_report['reporte_comentarios']= $this->report_model->count_comments($usuario_reporte->repo_id);
                $data_report['reporte_like']       = $this->report_model->like_comment($usuario_reporte->repo_id, $usuario);
                $data_report['seguidores']         = $this->reportes->obtenerSeguidores($usuario_reporte->repo_id, 'muro');

                if ($tipo_reporte=="problem") {
                    $tipo_reporte="Emergencia";
                }elseif ($tipo_reporte=="momment") {
                    $tipo_reporte="Momento Wof";
                }elseif ($tipo_reporte=="lost") {
                    $tipo_reporte="Perro perdido";
                }elseif ($tipo_reporte=="adoption") {
                    $tipo_reporte="Adopción";
                }
    			$avatar = ($usuario_reporte->usr_avatar == 'default_avatar.jpg')?base_url('graficas/'.$usuario_reporte->usr_avatar):base_url('files/'.$usuario_reporte->usr_id.'/'.$usuario_reporte->usr_avatar);

                if ($usuario!=$id_destinatario) {
                    //notificación por correo
                    if ($notificacion==1) {

                        $email2 = $email_destinatario;
                        $entrevistado_correo = $email_destinatario;
                        $email_data = array('id' => $id_destinatario, 'nombre' => $nombre_destinatario, 'comentario' => $comentario, 'comentarista' => $nombre_comentarista, 'reporte' => $descripcion_reporte, 'tipo' => $tipo_reporte, 'avatar' => $data['avatar']);

                        $email_view = $this->load->view('email_comentario', $email_data, TRUE);

                        $this->sendEmail($email2, $data['usuario'], $email_view, $entrevistado_correo, $this->input->post('mensaje'));
                    }

                    //Notificación Push
                    if ($push_comentario==1) {
                        //Parse
                        $app_id = 'ybxlt8zogjCYuOZf0mSb8oKgOF9y44F7OwLitxtc';
                        $rest_key = 'ipWy05MaRxIqywzZ6oL2cMiNRYxuRn9Vl4y09Ygz';
                        $master_key = '4eF2t3nEKEEehwv2prkBzEg4STXquSP7GSeoVf9K';

                        ParseClient::initialize( $app_id, $rest_key, $master_key );

                        $datos = array("alert" => $nombre_comentarista . " ha comentado: '" . $comentario . "' en tu reporte", "id_report" => $reporte, "reporte" => $data_report, "accion" => 2, "badge"=> "Increment", "sound" => "dog_bark6.caf");

                        // Push to Channels
                        ParsePush::send(array(
                            "channels" => ["user_" . $id_destinatario],
                            "data" => $datos
                        ));
                        //Fin PArse
                    }

                    //Log:
                    $this->reportes->crear_log_notificaciones($reporte, 2, $comentario, $id_destinatario, $nombre_comentarista . " ha comentado: " . $comentario . " en tu reporte", $usuario, $data['avatar'], $nombre_comentarista);
                }

                //Notificacion para seguidores
                $seguidores = $this->reportes->obtenerSeguidores($reporte, 'muro');

                foreach ($seguidores as $seguidor) {
                    //print_r($seguidor['id']);die();
                    //var_dump($seguidor->id);die();
                    //Parse

                    if ($seguidor['id']!=$usuario && $seguidor['notification_comment_follow']==1) {
                        $app_id = 'ybxlt8zogjCYuOZf0mSb8oKgOF9y44F7OwLitxtc';
                        $rest_key = 'ipWy05MaRxIqywzZ6oL2cMiNRYxuRn9Vl4y09Ygz';
                        $master_key = '4eF2t3nEKEEehwv2prkBzEg4STXquSP7GSeoVf9K';

                        ParseClient::initialize( $app_id, $rest_key, $master_key );

                        $datos = array("alert" => $nombre_comentarista . " ha comentado: '" . $comentario . "' en un reporte que sigues", "id_report" => $reporte, "reporte" => $data_report, "accion" => 2, "badge"=> "Increment", "sound" => "dog_bark6.caf");

                        // Push to Channels
                        ParsePush::send(array(
                            "channels" => ["user_" . $seguidor['id']],
                            "data" => $datos
                        ));
                        //Fin PArse

                        //Log:
                        $this->reportes->crear_log_notificaciones($reporte, 2, $comentario, $seguidor['id'], $nombre_comentarista . " ha comentado: '" . $comentario . "' en un reporte que sigues", $usuario, $data['avatar'], $nombre_comentarista);
                    }
                    
                }
                

    			$result['codigo']		= 1;
    			$result['mensaje']		= 'Comentario agregado con exito.';
    			$result['comentario']	= $data;
    		}
    		else {
    			$result['codigo']		= 0;
    			$result['mensaje']		= 'El comentario no pudo ser agregado.';
    		}
    	}

    	$this->response($result, 200);
    }
}