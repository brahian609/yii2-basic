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

class SiteController extends Controller
{
    /**
     * @inheritdoc
     */

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
