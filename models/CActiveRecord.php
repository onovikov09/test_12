<?php

namespace app\models;

use yii\db\ActiveRecord;

class CActiveRecord extends ActiveRecord
{
    const SCENARIO_CREATE = 'create';

    /**
     * @param $params
     * @return static
     */
    public static function createModel($params)
    {
        $model = new static(["scenario" => static::SCENARIO_CREATE]);
        $model->_setAttributes($params) && $model->save();
        return $model;
    }

    /**
     * @param $condition
     * @param array $params
     * @return CActiveRecord|null|static
     */
    public static function findOrCreate($condition, $params = [])
    {
        $model = static::findOne($condition);
        if ($model) {
            return $model;
        }

        return static::createModel($params);
    }

    /**
     * @param $params
     * @return bool
     */
    private function _setAttributes($params)
    {
        foreach ($params as $paramName => $paramValue) {
            if ($this->hasAttribute($paramName)) {
                $this->setAttribute($paramName, $paramValue);
            }
        }

        return true;
    }

    /**
     * @param $params
     * @return bool
     */
    public function updateModel($params)
    {
        return $this->_setAttributes($params) && $this->save();
    }
}