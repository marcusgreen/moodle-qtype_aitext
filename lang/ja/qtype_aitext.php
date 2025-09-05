<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'qtype_aitext', language 'ja'
 *
 * @package    qtype_aitext
 * @subpackage aitext
 * @copyright  2025 TAKUMI KATSURA
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['acceptedfiletypes'] = '許可されるファイルタイプ';
$string['addsample'] = 'サンプル回答を追加';
$string['aiprompt'] = 'AIプロンプト';
$string['aiprompt_help'] = 'AI採点システムへの指示文です。これはAIが学生の回答にフィードバックを与える際のガイドラインです。';
$string['aipromptmissing'] = 'AIプロンプトが未入力です。フィードバック生成の基準となるプロンプトを入力してください。';
$string['answerfiles'] = '回答ファイル';
$string['answertext'] = '回答テキスト';
$string['attachmentsoptional'] = '添付は任意';
$string['automatic_spellcheck'] = '自動スペルチェック';
$string['backends'] = 'AIバックエンドシステム';
$string['backends_text'] = 'Core AIシステムはMoodle 4.5で導入されました。Local AIシステムは https://github.com/mebis-lp/moodle-local_ai_manager 、Tool AIシステムは https://github.com/marcusgreen/moodle-tool_aiconnect です。';
$string['cachedef_stringdata'] = 'キャッシュ定義文字列データ';
$string['coreaisubsystem'] = 'コアAIサブシステム';
$string['defaultmarksscheme'] = '採点基準';
$string['defaultmarksscheme_setting'] = 'これは新規問題のデフォルト採点方式です。問題に合わせて作問者が調整してください。';
$string['defaultprompt'] = 'AIプロンプト';
$string['defaultprompt_setting'] = 'これは新規問題のデフォルトAIプロンプトです。AI採点システムに、学生の回答をどのように分析するかを指示します。これはAIが学生の回答にフィードバックを与える際のガイドラインです。作問者は問題に合わせて調整してください。';
$string['deletesample'] = 'サンプルを削除';
$string['disclaimer'] = '免責事項';
$string['disclaimer_setting'] = '各回答に付記されるテキスト。フィードバックが大規模言語モデルによるものであり、人間ではないことを示します。';
$string['err_invalidbackend'] = '無効なバックエンドエラー;';
$string['err_maxminmismatch'] = '最大語数制限は最小語数制限より大きくなければなりません。';
$string['err_maxwordlimit'] = '最大語数制限が有効ですが、設定されていません。';
$string['err_maxwordlimitnegative'] = '最大語数制限を負の数にすることはできません。';
$string['err_minwordlimit'] = '最小語数制限が有効ですが、設定されていません。';
$string['err_minwordlimitnegative'] = '最小語数制限を負の数にすることはできません。';
$string['err_parammissing'] = '無効なパラメータです。サンプル回答とプロンプトがあることを確認してください。';
$string['err_retrievingfeedback'] = 'AIツールからフィードバックを取得中にエラーが発生しました: {$a}';
$string['err_retrievingtranslation'] = '翻訳の取得中にエラーが発生しました: {$a}';
$string['formateditor'] = 'HTMLエディタ';
$string['formateditorfilepicker'] = 'ファイルピッカー付きHTMLエディタ';
$string['formatmonospaced'] = 'プレーンテキスト（等幅フォント）';
$string['formatnoinline'] = 'オンラインテキストなし';
$string['formatplain'] = 'プレーンテキスト';
$string['get_llmmfeedback'] = 'LLMフィードバックを取得';
$string['graderinfo'] = '採点者向け情報';
$string['graderinfoheader'] = '採点者情報';
$string['jsonprompt'] = 'JSONプロンプト';
$string['jsonprompt_setting'] = '戻り値をJSONに変換するための指示';
$string['localaimanager'] = 'ローカルAIマネージャ';
$string['markprompt_required'] = '採点プロンプト必須';
$string['markprompt_required_setting'] = '設定すると、問題作成時に採点用プロンプト入力が必須となり、空欄の場合エラーが表示されます。';
$string['markscheme'] = '採点基準';
$string['markscheme_help'] = 'AI採点システムに、学生の回答へ数値による点数を付与する方法を指示します。この問題の「デフォルト得点」が総得点になります。';
$string['markschememissing'] = '採点基準が未入力です。ユーザーの入力をどう採点するかのプロンプトを入力してください。';
$string['maxwordlimit'] = '最大語数制限';
$string['maxwordlimit_help'] = '学生がテキストを入力する必要がある場合、ここで設定した語数が提出可能な最大値となります。';
$string['maxwordlimitboundary'] = 'この問題の語数制限は {$a->limit} 語です。現在 {$a->count} 語を入力しています。回答を短くして再度試してください。';
$string['minwordlimit'] = '最小語数制限';
$string['minwordlimit_help'] = '学生がテキストを入力する必要がある場合、ここで設定した語数が提出可能な最小値となります。';
$string['minwordlimitboundary'] = 'この問題には最低 {$a->limit} 語の回答が必要です。現在 {$a->count} 語しか入力されていません。回答を増やして再度試してください。';
$string['model'] = 'モデル';
$string['nlines'] = '{$a} 行';
$string['pluginname'] = 'AIテキスト';
$string['pluginname_help'] = '学生は問題に対してテキストを入力します。回答テンプレートを指定することもできます。回答はまずAIシステム（例: ChatGPT）によって暫定的に採点され、その後手動で採点できます。';
$string['pluginname_link'] = 'question/type/AI Text';
$string['pluginname_userfaced'] = 'AIによるフィードバック生成をサポートする「AIテキスト」問題タイプ';
$string['pluginnameadding'] = 'AIテキスト問題を追加';
$string['pluginnameediting'] = 'AIテキスト問題を編集';
$string['pluginnamesummary'] = 'ファイルアップロードやオンラインテキストでの回答を受け付けます。学生の回答は設定されたAI/大規模言語モデルで処理され、フィードバックと必要に応じて得点が返されます。';
$string['privacy::responsefieldlines'] = '入力ボックス（テキストエリア）の行数';
$string['privacy:metadata'] = 'AIテキスト問題タイププラグインは、作問者が既定のオプションをユーザー設定として保存できます。';
$string['privacy:preference:attachments'] = '許可される添付ファイル数';
$string['privacy:preference:attachmentsrequired'] = '必須添付ファイル数';
$string['privacy:preference:defaultmark'] = '問題に設定されたデフォルト得点';
$string['privacy:preference:disclaimer']  = 'フィードバックや採点がLLMによることを示すテキスト';
$string['privacy:preference:maxbytes'] = '最大ファイルサイズ';
$string['privacy:preference:responseformat'] = '回答形式（HTMLエディタ、プレーンテキストなど）';
$string['prompt'] = 'プロンプト';
$string['prompt_setting'] = 'AIシステムに渡されるプロンプトの外枠となるテキストです。[responsetext] には学生が入力した回答が入ります。問題に設定されたAIプロンプトの値がここに追加されます。';
$string['purposeplacedescription_feedback'] = '小テストの解答送信時や再採点時に、フィードバック候補を生成します。';
$string['purposeplacedescription_translate'] = '免責文およびAI生成フィードバックをユーザーの対象言語に翻訳します。';
$string['response'] = '回答';
$string['responsefieldlines'] = '入力ボックスの行数';
$string['responseformat'] = '回答形式';
$string['responseformat_setting'] = '学生が回答に使用するエディタ';
$string['responseisrequired'] = '学生のテキスト入力を必須にする';
$string['responsenotrequired'] = 'テキスト入力は任意';
$string['responseoptions'] = '回答オプション';
$string['responsetemplate'] = '回答テンプレート';
$string['responsetemplate_help'] = 'ここに入力したテキストは、新しい解答を開始するときに回答入力欄に表示されます。';
$string['responsetemplateheader'] = '回答テンプレート';
$string['responsetester'] = '回答テストツール';
$string['responsetesthelp'] = '回答テストヘルプ';
$string['responsetesthelp_help'] = 'フォーム保存時に保存されるのはテスト回答のみであり、LLMからの返却値は保存されません。';
$string['responsetests'] = '複数回答からのテスト出力';
$string['sampleresponse'] = 'サンプル回答';
$string['sampleresponse_help'] = 'サンプル回答を使用して、AI採点システムがどのように応答するかをテストできます。';
$string['sampleresponseempty'] = 'AIプロンプトとサンプル回答があることを確認してください。';
$string['sampleresponseeval'] = 'サンプル回答の評価';
$string['sampleresponseevaluate'] = 'サンプル回答を評価';
$string['showprompt'] = 'プロンプトを表示';
$string['spellcheck_editor_desc'] = 'AIによってスペルミスが修正されたテキストです。この提案を編集できます。';
$string['spellcheck_prompt'] = '以下のテキストを一字一句再現してください。フィードバックは一切与えないでください。ただし、すべてのスペルミスを修正してください: ';
$string['spellcheck_student_anser_desc'] = 'これは学生の元の回答です';
$string['spellcheckedit'] = 'スペルチェックを編集';
$string['spellcheckeditor'] = 'AIベースのスペルチェックを編集';
$string['testresponses'] = 'テスト回答';
$string['thedefaultmarksscheme'] = '文法またはスペルの誤り1つにつき合計点から1点減点します。';
$string['thedefaultprompt'] = '文中の文法やスペルに問題があれば説明してください。';
$string['toolaimanager'] = 'ツールAIマネージャ';
$string['translatepostfix'] = '翻訳用接尾辞';
$string['translatepostfix_text'] = 'プロンプトの末尾に「フィードバックを言語 .current_language() に翻訳する」が追加されます。';
$string['use_local_ai_manager'] = 'local_ai_manager プラグインが提供するAIバックエンドを使用する';
$string['use_local_ai_manager_setting'] = 'AI関連の処理に local_ai_manager プラグインを使用します（インストールが必要です）';
$string['wordcount'] = '語数: {$a}';
$string['wordcounttoofew'] = '語数: {$a->count}、必要語数未満 {$a->limit} 語。';
$string['wordcounttoomuch'] = '語数: {$a->count}、上限語数を超過 {$a->limit} 語。';
