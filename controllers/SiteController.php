<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\helpers\Url;
use app\models\ContactForm;
use bitcko\googlecalendar\GoogleCalendarApi;

class SiteController extends Controller {

    /**
     * {@inheritdoc}
     */
    public function behaviors() {
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
     * {@inheritdoc}
     */
    public function actions() {
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

    public function actionAuth() {

        $redirectUrl = Url::to(['/site/index'], true);
        //$calendarId = '810687770454-kkao937rc4bjc92ibbmv2hri9k685sqs.apps.googleusercontent.com';
        $calendarId = 'primary';
        $username = "vladan.calendar";
        $googleApi = new GoogleCalendarApi($username, $calendarId, $redirectUrl);
        if (!$googleApi->checkIfCredentialFileExists()) {
            $googleApi->generateGoogleApiAccessToken();
        }
        \Yii::$app->response->data = "Google api authorization done";
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex() {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post())) {
            $redirectUrl = Url::to(['/site/index'], true);
            //$calendarId = '810687770454-kkao937rc4bjc92ibbmv2hri9k685sqs.apps.googleusercontent.com';
            $calendarId = 'primary';
            $username = "vladan.calendar";
            $googleApi = new GoogleCalendarApi($username, $calendarId, $redirectUrl);

            if ($googleApi->checkIfCredentialFileExists()) {
                $event = array(
                    'summary' => $model->name,
                    'location' => 'Belgrade',
                    'description' => $model->note,
                    'start' => array(
                        'dateTime' => $model->date . 'T' . $model->time,
                        'timeZone' => 'Europe/Belgrade',
                    ),
                    'end' => array(
                        'dateTime' => $model->date . 'T' . $model->time,
                        'timeZone' => 'Europe/Belgrade',
                    ),
                    'recurrence' => array(
                        'RRULE:FREQ=DAILY;COUNT=2'
                    ),
                    'attendees' => array(
                        array('email' => $model->email)
                    ),
                    'reminders' => array(
                        'useDefault' => FALSE,
                        'overrides' => array(
                            array('method' => 'email', 'minutes' => 15),
                            array('method' => 'popup', 'minutes' => 10),
                        ),
                    ),
                );
                
                $calEvent = $googleApi->createGoogleCalendarEvent($event);
                
                //die($calEvent);
                
                \Yii::$app->response->data = "New event added with id: " . $calEvent->getId();
            } else {
                return $this->redirect(['auth']);
            }  

            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
                    'model' => $model,
        ]);
    }

}
