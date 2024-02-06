<?php

class UserController {
    
    // ユーザーの一覧を表示するメソッド
    public function index() {
        // ユーザーのデータを取得する処理
        $users = // データベースからユーザー情報を取得するなどの処理;

        // ユーザー一覧のビューを表示
        // この部分は実際のプロジェクトに合わせて適切なビューを表示する処理に置き換える必要があります
        include 'views/user/index.php';
    }

    // ユーザーを新規作成するメソッド
    public function create() {
        // 新規ユーザーのフォームが送信された場合の処理
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 入力データの検証やサニタイズなどの処理

            // データベースに新規ユーザーを保存する処理

            // ユーザー一覧ページにリダイレクトなどの処理
            header('Location: /user/index');
            exit;
        }

        // 新規ユーザー作成のフォームを表示
        // この部分は実際のプロジェクトに合わせて適切なビューを表示する処理に置き換える必要があります
        include 'views/user/create.php';
    }

    // ユーザーの詳細情報を表示するメソッド
    public function show($userId) {
        // 特定のユーザーのデータを取得する処理
        $user = // データベースから特定のユーザー情報を取得するなどの処理;

        // ユーザー詳細のビューを表示
        // この部分は実際のプロジェクトに合わせて適切なビューを表示する処理に置き換える必要があります
        include 'views/user/show.php';
    }

    // ユーザー情報を更新するメソッド
    public function update($userId) {
        // ユーザー情報の更新が送信された場合の処理
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 入力データの検証やサニタイズなどの処理

            // データベースのユーザー情報を更新する処理

            // ユーザー詳細ページにリダイレクトなどの処理
            header('Location: /user/show/' . $userId);
            exit;
        }

        // ユーザー情報更新のフォームを表示
        // この部分は実際のプロジェクトに合わせて適切なビューを表示する処理に置き換える必要があります
        include 'views/user/update.php';
    }

    // ユーザーを削除するメソッド
    public function delete($userId) {
        // ユーザーの削除がリクエストされた場合の処理
        // データベースからユーザーを削除する処理など

        // ユーザー一覧ページにリダイレクトなどの処理
        header('Location: /user/index');
        exit;
    }
}

?>
