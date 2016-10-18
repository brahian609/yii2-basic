<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use app\models\ValidarFormulario;
use app\models\ValidarFormularioAjax;
use yii\widgets\ActiveForm;
use yii\web\response;
use app\models\FormContactos;
use app\models\Contactos;
use app\models\FormSearch;
use yii\helpers\Html;
use yii\data\Pagination;
use yii\helpers\Url;
use app\models\FormRegister;
use app\models\Users;
use yii\web\Session;
use app\models\FormRecoverPass;
use app\models\FormResetPass;

class SiteController extends Controller
{
    /**
     * @inheritdoc
     */

    public function actionRecoverpass()
    {
        $model = new FormRecoverPass;
        $msg = null;

        if ($model->load(Yii::$app->request->post())) {
            if ($model->validate()) {
                $table = Users::find()->where("email=:email", [":email" => $model->email]);
                //Si el usuario existe
                if ($table->count() == 1) {
                    //Crear variables de sesión para limitar el tiempo de restablecido del password
                    //hasta que el navegador se cierre
                    $session = new Session;
                    $session->open();

                    $session["recover"] = $this->randKey("abcdef0123456789", 200);
                    $recover = $session["recover"];

                    $table = Users::find()->where("email=:email", [":email" => $model->email])->one();
                    $session["id_recover"] = $table->id;

                    $verification_code = $this->randKey("abcdef0123456789", 8);
                    $table->verification_code = $verification_code;
                    $table->save();

                    $subject = "Recuperar password";
                    $body = "<p>Copie el siguiente código de verificación para restablecer su password ... ";
                    $body .= "<strong>" . $verification_code . "</strong></p>";
                    $body .= "<p><a href='http://localhost/yii2-basic/web/index.php?r=site/resetpass'>Recuperar password</a></p>";

                    //Enviamos el correo
                    Yii::$app->mailer->compose()
                        ->setTo($model->email)
                        ->setFrom([Yii::$app->params["adminEmail"] => Yii::$app->params["title"]])
                        ->setSubject($subject)
                        ->setHtmlBody($body)
                        ->send();

                    $model->email = null;
                    $msg = "Le hemos enviado un mensaje a su cuenta de correo para que pueda resetear su password";
                } else {
                    $msg = "Ha ocurrido un error";
                }
            } else {
                $model->getErrors();
            }
        }
        return $this->render("recoverpass", [
            "model" => $model,
            "msg" => $msg
        ]);
    }

    public function actionResetpass()
    {
        $model = new FormResetPass;
        $msg = null;

        $session = new Session;
        $session->open();

        if (empty($session["recover"]) || empty($session["id_recover"])) {
            return $this->redirect(["site/index"]);
        } else {
            $recover = $session["recover"];
            $model->recover = $recover;
            $id_recover = $session["id_recover"];
        }

        if ($model->load(Yii::$app->request->post())) {
            if ($model->validate()) {
                if ($recover == $model->recover) {
                    $table = Users::findOne(["email" => $model->email, "id" => $id_recover, "verification_code" => $model->verification_code]);
                    $table->password = crypt($model->password, Yii::$app->params["salt"]);

                    if ($table->save()) {
                        $session->destroy();

                        $model->email = null;
                        $model->password = null;
                        $model->password_repeat = null;
                        $model->recover = null;
                        $model->verification_code = null;

                        $msg = "Password recuperado exitosamente, redireccionando...";
                        $msg .= "<meta http-equiv='refresh' content='5; " . Url::toRoute("site/login") . "'>";
                    } else {
                        $msg = "Ha ocurrido un error";
                    }

                } else {
                    $model->getErrors();
                }
            }
        }

        return $this->render("resetpass", [
            "model" => $model,
            "msg" => $msg
        ]);

    }

    private function randKey($str = '', $long = 0)
    {
        $key = null;
        $str = str_split($str);
        $start = 0;
        $limit = count($str) - 1;
        for ($x = 0; $x < $long; $x++) {
            $key .= $str[rand($start, $limit)];
        }
        return $key;
    }

    public function actionConfirm()
    {
        $table = new Users;
        if (Yii::$app->request->get()) {
            $id = Html::encode($_GET["id"]);
            $authKey = $_GET["authKey"];

            if ((int)$id) {
                $model = $table
                    ->find()
                    ->where("id=:id", [":id" => $id])
                    ->andWhere("authKey=:authKey", [":authKey" => $authKey]);

                if ($model->count() == 1) {
                    $activar = Users::findOne($id);
                    $activar->activate = 1;
                    if ($activar->update()) {
                        echo "Registro finalizado correctamente, redireccionando ...";
                        echo "<meta http-equiv='refresh' content='8; " . Url::toRoute("site/login") . "'>";
                    } else {
                        echo "Ha ocurrido un error al realizar el registro, redireccionando ...";
                        echo "<meta http-equiv='refresh' content='8; " . Url::toRoute("site/login") . "'>";
                    }
                } else {
                    return $this->redirect(["site/login"]);
                }
            } else {
                return $this->redirect(["site/login"]);
            }
        }
    }

    public function actionRegister()
    {
        $model = new FormRegister;
        $msg = null;

        if ($model->load(Yii::$app->request->post()) && Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }

        if ($model->load(Yii::$app->request->post())) {
            if ($model->validate()) {
                $table = new Users;
                $table->username = $model->username;
                $table->email = $model->email;

                $table->password = crypt($model->password, Yii::$app->params["salt"]);

                //clave será utilizada para activar el usuario
                $table->authKey = $this->randKey("abcdef0123456789", 200);
                //Creamos un token de acceso único para el usuario
                $table->accessToken = $this->randKey("abcdef0123456789", 200);

                if ($table->insert()) {
                    $user = $table->find()->where(["email" => $model->email])->one();
                    $id = urlencode($user->id);
                    $authKey = urlencode($user->authKey);

                    $subject = "Confirmar registro";
                    $body = "<h1>Haga click en el siguiente enlace para finalizar tu registro</h1>";
                    $body .= "<a href='http://localhost/yii2-basic/web/index.php?r=site/confirm&id=" . $id . "&authKey=" . $authKey . "'>Confirmar</a>";

                    //enviar correo
                    Yii::$app->mailer->compose()
                        ->setTo($user->email)
                        ->setFrom([Yii::$app->params["adminEmail"] => Yii::$app->params["title"]])
                        ->setSubject($subject)
                        ->setHtmlBody($body)
                        ->send();

                    $model->username = null;
                    $model->email = null;
                    $model->password = null;
                    $model->password_repeat = null;

                    $msg = "Registro exitoso, sólo falta confirmar tu registro en tu cuenta de correo";
                } else {
                    $msg = "Ha ocurrido un error al llevar a cabo tu registro";
                }

            } else {
                $model->getErrors();
            }
        }
        return $this->render("register", ["model" => $model, "msg" => $msg]);
    }

    public  function actionUpdate()
    {
        $model = new FormContactos();
        $msg = null;

        if($model->load(Yii::$app->request->post()))
        {
            if($model->validate())
            {
                $table = Contactos::findOne($model->id);
                if($table)
                {
                    $table->name_first = $model->name_first;
                    $table->name_last = $model->name_last;
                    $table->email = $model->email;

                    if ($table->update())
                    {
                        $msg = "El Contacto ha sido actualizado correctamente";
                    }
                    else
                    {
                        $msg = "El Contacto no ha podido ser actualizado";
                    }
                }
                else
                {
                    $msg = "El contacto seleccionado no ha sido encontrado";
                }
            }
            else
            {
                $model->getErrors();
            }
        }


        if (Yii::$app->request->get("id"))
        {
            $id = Html::encode($_GET["id"]);
            if ((int) $id)
            {
                $table = Contactos::findOne($id);
                if($table)
                {
                    $model->id = $table->id;
                    $model->name_first = $table->name_first;
                    $model->name_last = $table->name_last;
                    $model->email = $table->email;
                }
                else
                {
                    return $this->redirect(["site/view"]);
                }
            }
            else
            {
                return $this->redirect(["site/view"]);
            }
        }
        else
        {
            return $this->redirect(["site/view"]);
        }
        return $this->render("update", ["model" => $model, "msg" => $msg]);
    }

    public function actionDelete()
    {
        if(Yii::$app->request->get())
        {
            $id = Html::encode($_GET["id"]);
            if((int) $id)
            {
                if(Contactos::deleteAll("id=:id", [":id" => $id]))
                {
                    return $this->redirect(["site/view"]);
                }
                else
                {
                    echo "Ha ocurrido un error al eliminar el registro, redireccionando ...";
                    echo "<meta http-equiv='refresh' content='3; ".Url::toRoute("site/view")."'>";
                }
            }
            else
            {
                echo "Ha ocurrido un error al eliminar el registro, redireccionando ...";
                echo "<meta http-equiv='refresh' content='3; ".Url::toRoute("site/view")."'>";
            }
        }
        else
        {
            return $this->redirect(["site/view"]);
        }
    }

    public function actionView()
    {
        $form = new FormSearch();
        $search = null;
        if($form->load(Yii::$app->request->get())) {
            if($form->validate()) {
                $search = Html::encode($form->q);
                $table = Contactos::find()
                         ->where(["like", "id", $search])
                         ->orWhere(["like", "name_first", $search])
                         ->orWhere(["like", "name_last", $search]);
                $count = clone $table;
                $pages = new Pagination([
                    "pageSize" => 2,
                    "totalCount" => $count->count()
                ]);
                $model = $table->offset($pages->offset)
                         ->limit($pages->limit)
                         ->all();
            }else{
                $form->getErrors();
            }
        }else{
            $table = Contactos::find();
            $count = clone $table;
            $pages = new Pagination([
                "pageSize" => 2,
                "totalCount" => $count->count()
            ]);
            $model = $table->offset($pages->offset)
                ->limit($pages->limit)
                ->all();
        }

        return $this->render('view', [
            'model' => $model,
            'form' => $form,
            'search' => $search,
            "pages" => $pages,
        ]);
    }

    public function actionCreate()
    {
        $model = new FormContactos();
        $msg = null;

        if($model->load(Yii::$app->request->post())) {
            if($model->validate()) {
                $table = new Contactos();
                $table->name_first = $model->name_first;
                $table->name_last = $model->name_last;
                $table->email = $model->email;
                $table->created_at = date('Y-m-d H:i:s');
                if($table->insert()) {
                    $msg = "Registro guardado correctamente";
                    $model->name_first = null;
                    $model->name_last = null;
                    $model->email = null;
                }else{
                    $msg = "Ha ocurrido un error al guardar el registro";
                }
            }else{
                $model->getErrors();
            }
        }
        return $this->render("create", [
            'model' => $model,
            'msg' => $msg,
        ]);
    }

    public function actionSaludo($get = 'Parametros get')
    {
        $mensaje = 'Hola Mundo';
        return $this->render("saludo",
            [
                "mensaje" => $mensaje,
                "get" => $get
            ]
        );
    }

    public function actionFormulario($mensaje = null)
    {
        return $this->render("formulario", ["mensaje" => $mensaje]);
    }

    public function actionRequest()
    {
        $mensaje = null;

        if($_REQUEST['nombre']) {
            $mensaje = "El nombre ingresado es: ". $_REQUEST['nombre'];
        }

        $this->redirect(["site/formulario", "mensaje" => $mensaje]);
    }

    public function actionValidarformulario()
    {
        $model = new ValidarFormulario();
        if($model->load(Yii::$app->request->post())) {
            if($model->validate()) {
                //insert, delete, etc
            }else{
                $model->getErrors();
            }
        }

        return $this->render("validarformulario", [
                'model' => $model,
        ]);
    }

    public function actionValidarformularioajax()
    {
        $model = new ValidarFormularioAjax();
        $msg = null;

        if($model->load(Yii::$app->request->post()) && Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }
        if($model->load(Yii::$app->request->post())) {
            if($model->validate()) {
                //insert, delete, etc
                $msg = "Formulario enviado correctamente";
                $model->nombre = null;
                $model->email = null;
            }else{
                return $model->getErrors();
            }
        }

        return $this->render("validarformularioajax", [
            "model" => $model,
            "msg" => $msg,
        ]);
    }

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Login action.
     *
     * @return string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return string
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }
}
