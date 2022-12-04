def carousel
    {
        "type": "template",
        "altText": "this is a carousel template",
        "template": {
            "type": "carousel",
            "columns": [
                {
                    "title": "支払い操作",
                    "text": "支払い操作です！",
                    "actions": [
                        {
                            "type": "postback",
                            "label": "登録",
                            "data": "action=chargeAdd",
                            "displayText": ""
                        },
                        {
                            "type": "postback",
                            "label": "一覧",
                            "data": "action=botList"
                        },
                        {
                            "type": "postback",
                            "label": "清算",
                            "data": "action=botCalc"
                        }
                    ]
                },
                {
                    "title": "ユーザー操作",
                    "text": "ユーザー操作です！",
                    "actions": [
                        {
                            "type": "postback",
                            "label": "参加",
                            "data": "action=botJoin"
                        },
                        {
                            "type": "postback",
                            "label": "一覧",
                            "data": "action=botUserList"
                        },
                        {
                            "type": "postback",
                            "label": "ダミー",
                            "data": "これはダミーです"
                        }
                    ]
                },
                {
                    "title": "支払い操作(追加機能)",
                    "text": "支払い操作(追加機能)です！",
                    "actions": [
                        {
                            "type": "postback",
                            "label": "立替された合計額",
                            "data": "action=botSum"
                        },
                        {
                            "type": "postback",
                            "label": "! 支払い削除 !",
                            "data": "action=chargeDelete"
                        },
                        {
                            "type": "postback",
                            "label": "ダミー",
                            "data": "これはダミーです"
                        }
                    ]
                }
            ]
        }
    }
end