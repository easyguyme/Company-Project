<?php
namespace backend\models;

use backend\components\BaseModel;
use backend\utils\StringUtil;
use backend\utils\MongodbUtil;
use Yii;
use mongoId;

/**
 * This is the graphic class for aug-marketing.
 * The followings are the available columns in collection 'token':
 * @property MongoId $id
 * @property MongoId $accountId
 * @property int $usedCount
 * @property array $articles
 * @property boolean $isDeleted
 * @property MongoDate $createdAt
 * @property MongoDate $updatedAt
 *
 * attributes for article:
 * title: string,
 * description: string,
 * picUrl: string,
 * content: string(html)
 * contentUrl: string(link)
 * sourceUrl: string(link)
 *
 * @author Devin Jin
 **/
class Graphic extends BaseModel
{
    const MESSAGE_TYPE = 'NEWS';

    /**
     * Declares the name of the Mongo collection associated with token.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'graphic';
    }

     /**
     * Returns the list of all attribute names of token.
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return array_merge(parent::attributes(), ['accountId', 'articles', 'usedCount', 'type']);
    }

    /**
     * Returns the list of all safeattribute names of token.
     * @return array list of attribute names.
     */
    public function safeAttributes()
    {
        return array_merge(parent::safeAttributes(), ['accountId', 'articles', 'usedCount', 'type']);
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into user.
     */
    public function fields()
    {
        return array_merge(parent::fields(), [
            'articles',
            'usedCount',
            'createdAt' => function () {
                return MongodbUtil::MongoDate2String($this->createdAt, 'Y-m-d');
            }
        ]);
    }

    /**
     * Returns the list of all rules of user.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                ['type', 'default', 'value' => count($this->articles) == 1 ? "single" : "multiple"],
                ['usedCount', 'default', 'value' => 0],
                ['articles', 'validateArticles']
            ]
        );
    }

    /**
     * Validator for field 'articles'
     * @author Devin.Jin
     **/
    public function validateArticles($attribute)
    {
        //only validate the field "article"
        if ($attribute !== 'articles') {
            return true;
        }

        $articles = $this->$attribute;

        //the articles should be an array whose length is less than 8
        if (!is_array($articles)) {
            $this->addError($attribute, 'articles should be an array');
        }

        if (count($articles) > 8) {
            $this->addError($attribute, 'The counts of the article should not exceed 8');
        }

        $requiredFields = ['title', 'picUrl', 'content'];

        //validate each item in articles
        foreach ($articles as $article) {
            //validate the required fields in articles
            foreach ($requiredFields as $field) {
                if (empty($article[$field])) {
                    $this->addError($attribute, 'articles.' . $field . ' is required.');
                }
            }

            //if the field sourceUrl exsists, it should be an url
            if (!empty($article['sourceUrl'])) {
                $reg = '/^http(s)?:\/\//i';

                if (!preg_match($reg, $article['sourceUrl'])) {
                    // preg_match($reg, $article['sourceUrl'], $matches);var_dump($article['sourceUrl']);die;
                    $this->addError($attribute, 'sourceUrl must be a correct link.');
                }
            }
        }
    }

    /**
     * make the usedCount of the graphic increased by 1
     * @param MongoId, $id
     * @author Devin
     **/
    public static function incUsedCount($id)
    {
        return self::updateAll(['$inc' => ['usedCount' => 1]], ['_id' => $id]);
    }

    /**
     * This method is called at the beginning of inserting or updating a record.
     * The default implementation will trigger an [[EVENT_BEFORE_INSERT]] event when `$insert` is true,
     * or an [[EVENT_BEFORE_UPDATE]] event if `$insert` is false.
     *
     * @param boolean $insert whether this method called while inserting a record.
     * If false, it means the method is called while updating a record.
     * @return boolean whether the insertion or updating should continue.
     * If false, the insertion or updating will be cancelled.
     */
    public function beforeSave($insert)
    {
        $staticPageService = Yii::$app->staticPageService;
        $articles = $this->articles;

        foreach ($articles as &$article) {
            $articleContent = $this->generateHtmlFile($article);
            $article['contentUrl'] = $staticPageService->generateQiniuFile($articleContent);
        }

        $this->articles = $articles;
        return parent::beforeSave($insert);
    }

    /**
     * Generate a html file with article
     * @param $article
     * @return string
     */
    public function generateHtmlFile($article)
    {
        $sourceUrl = '';
        $language = Yii::$app->language;
        if (!empty($article['sourceUrl'])) {
            if ($language == 'en_us') {
                $sourceUrl = '<a class="source" target="_blank" href="' . $article['sourceUrl'] . '">read the original text</a>';
            }
            if ($language == 'zh_cn') {
                $sourceUrl = '<a class="source" target="_blank" href="' . $article['sourceUrl'] . '">阅读原文</a>';
            }
            if ($language == 'zh_tr') {
                $sourceUrl = '<a class="source" target="_blank" href="' . $article['sourceUrl'] . '">閱讀原文</a>';
            }
        }

        $content =
            '<html>
            <head>
              <meta charset="UTF-8">
              <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
              <title>' . $article['title'] . '</title>
              <style type="text/css">
                body {
                  font-size: 14px;
                  color: #333333;
                  margin: 8px 20px;
                }
                .title {
                  margin-bottom: 10px;
                }
                .author {
                  font-size: 12px;
                  padding-top: 10px;
                  border-top: 1px solid #EEEEEE;
                }
                .main-img {
                    width:100%;
                }
                img {
                    max-width: 100%;
                }
                a.source, a.source:hover, a.source:focus {
                    text-decoration: none;
                }
                a {
                    color: #909DAD;
                }
                p {
                    word-wrap: break-word;
                }
              </style>
            </head>
            <body>
              <h1 class="title">' . $article['title'] . '</h1>
              <p class="author">' . date('Y-m-d') . '</p>
              <img class="main-img" src="' . $article['picUrl'] . '">
              <div>' .
                $article['content'] .
              '</div>' .
              $sourceUrl .
            '</body>
            </html>';
        return $content;
    }
}
