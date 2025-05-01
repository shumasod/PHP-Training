<?php

namespace App\Http\Requests;

/**
 * フォームリクエスト用のユーティリティトレイト
 * 
 * 画面やコンテキストに応じたバリデーションルールを管理します
 */
trait FormRequestTrait
{
    /**
     * バリデーションルールのコンテキストを保持
     *
     * @var string|null
     */
    protected $validationContext = null;

    /**
     * バリデーションコンテキストを設定する
     *
     * @param string $context
     * @return $this
     */
    public function setValidationContext(string $context)
    {
        $this->validationContext = $context;
        return $this;
    }

    /**
     * 現在のコンテキストに基づいてバリデーションルールを取得する
     *
     * @return array
     */
    public function rules()
    {
        // コンテキストに基づいたメソッド名を生成
        $methodName = 'rulesFor' . ucfirst($this->validationContext ?: 'Default');
        
        // メソッドが存在する場合は呼び出し、存在しない場合はデフォルトルールを使用
        if (method_exists($this, $methodName)) {
            return $this->mergeCommonRules($this->$methodName());
        }
        
        return $this->mergeCommonRules($this->rulesForDefault());
    }

    /**
     * 共通のバリデーションルールを定義
     *
     * @return array
     */
    protected function commonRules()
    {
        return [
            // すべての画面で共通して使用するルールをここに定義
            'csrf_token' => ['required'],
        ];
    }

    /**
     * 共通ルールと特定のコンテキストルールをマージする
     *
     * @param array $contextRules
     * @return array
     */
    protected function mergeCommonRules(array $contextRules)
    {
        return array_merge($this->commonRules(), $contextRules);
    }

    /**
     * デフォルトのバリデーションルール
     * これはコンテキストが設定されていない場合に使用される
     *
     * @return array
     */
    protected function rulesForDefault()
    {
        return [];
    }

    /**
     * 画面A用のバリデーションルールを作成する
     *
     * @return array
     */
    protected function rulesForScreenA()
    {
        return [
            'email' => [
                'nullable',
                'email:rfc,dns',
                'max:255',
            ],
            'name' => [
                'nullable',
                'string',
                'max:100',
            ],
            // 他の画面A用のルールを追加できます
        ];
    }

    /**
     * 画面B用のバリデーションルールを作成する
     *
     * @return array
     */
    protected function rulesForScreenB()
    {
        return [
            'email' => [
                'required',
                'email:rfc,dns',
                'unique:users,email',
                'max:255',
            ],
            'name' => [
                'required',
                'string',
                'max:100',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
            // 他の画面B用のルールを追加できます
        ];
    }

    /**
     * 画面C用のバリデーションルール（新しく追加）
     *
     * @return array
     */
    protected function rulesForScreenC()
    {
        return [
            'email' => [
                'required',
                'email:rfc,dns',
                'exists:users,email',
                'max:255',
            ],
            // 他の画面C用のルールを追加できます
        ];
    }

    /**
     * カスタムバリデーションメッセージを定義する
     *
     * @return array
     */
    public function messages()
    {
        return array_merge($this->commonMessages(), $this->contextMessages());
    }

    /**
     * 共通のバリデーションメッセージを定義
     *
     * @return array
     */
    protected function commonMessages()
    {
        return [
            'required' => ':attributeは必須項目です。',
            'email' => ':attributeは有効なメールアドレス形式で入力してください。',
            'max' => ':attributeは:max文字以内で入力してください。',
            'min' => ':attributeは:min文字以上で入力してください。',
            'unique' => 'この:attributeはすでに使用されています。',
            'confirmed' => ':attributeが確認用と一致しません。',
            'exists' => '指定された:attributeは存在しません。',
        ];
    }

    /**
     * コンテキスト固有のバリデーションメッセージを定義
     *
     * @return array
     */
    protected function contextMessages()
    {
        // コンテキストに基づいたメソッド名を生成
        $methodName = 'messagesFor' . ucfirst($this->validationContext ?: 'Default');
        
        // メソッドが存在する場合は呼び出し、存在しない場合は空の配列を返す
        if (method_exists($this, $methodName)) {
            return $this->$methodName();
        }
        
        return [];
    }

    /**
     * 画面A用のカスタムバリデーションメッセージ
     *
     * @return array
     */
    protected function messagesForScreenA()
    {
        return [
            'email.email' => 'メールアドレスの形式が正しくありません。',
            // 他の画面A用のカスタムメッセージを追加できます
        ];
    }

    /**
     * 画面B用のカスタムバリデーションメッセージ
     *
     * @return array
     */
    protected function messagesForScreenB()
    {
        return [
            'email.required' => 'メールアドレスは必ず入力してください。',
            'email.unique' => 'このメールアドレスは既に登録されています。',
            'password.min' => 'パスワードは8文字以上で設定してください。',
            // 他の画面B用のカスタムメッセージを追加できます
        ];
    }

    /**
     * 属性名のカスタマイズ
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'email' => 'メールアドレス',
            'name' => '氏名',
            'password' => 'パスワード',
            'password_confirmation' => 'パスワード（確認用）',
            // 他の属性名を追加できます
        ];
    }

    /**
     * バリデーション前の処理
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // 入力データの前処理を行うことができます
        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower($this->input('email')),
            ]);
        }
    }

    /**
     * バリデーション後の処理
     *
     * @return void
     */
    protected function afterValidation()
    {
        // バリデーション後の処理を行うことができます
    }
}
