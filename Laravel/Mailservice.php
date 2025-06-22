if ($result) {
            // メール送信（エラーハンドリング付き）
            try {
                $this->mailService->sendOptionCancelledMail(  // ← 正しいメソッド名
                    user: $user,
                    apply_option_ids: [],
                    cancel_option_ids: [$inputs['option_id']]
                );
            } catch (\Exception $e) {
                // メール送信エラーは処理を継続（ユーザー体験を優先）
                // エラーログは既存のcriticalErrorServiceで記録される
            }

            // 成功であればオプション申し込み画面に遷移する
            return $this->redirectSuccess(
                message: 'その他オプションキャンセルに成功しました。',
                route: 'mypage.option.index'
            );
        } else {
            return $this->redirectError(
                message: 'その他オプションキャンセルに失敗しました',
                route: 'mypage.option.index'
            );
        }
    }
