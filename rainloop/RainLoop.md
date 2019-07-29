u# RainLoop

## Getting started

1. Install node.js - `https://nodejs.org/download/`
2. Install yarn - `https://yarnpkg.com/en/docs/install`
3. Install gulp - `npm install gulp -g`
4. (option) Fork rainloop - `https://github.com/RainLoop/rainloop-webmail/issues/new#fork-destination-box`
5. Clone rainloop - `git clone https://github.com/RainLoop/rainloop-webmail.git rainloop`
6. `cd rainloop`
7. Install install all dependencies - `yarn install`
8. Run gulp - `gulp`

---

**Directory Structure**

```
rainloop
├── assets          # 網頁素材
├── build
│   └── owncloud    # ownCloud/NextCloud 套件
├── data            # 系統設定檔、Cache、紀錄檔...等
├── dev             # js 檔案
├── plugins         # 插件檔案
├── rainloop        # 核心程式碼
├── tests           # 單元測試
└── vendors         # 相依套件
```

```
/rainloop/v/0.0.0/app
├── domains               # 網域相關設定檔
├── libraries             # 函式庫
│   ├── Facebook
│   ├── Imagine
│   ├── lessphp
│   ├── MailSo            # 郵件相關
│   ├── Mobile_Detect
│   ├── pclzip
│   ├── PHPGangsta
│   ├── PHP-OAuth2
│   ├── phpseclib
│   ├── PHPThumb
│   ├── Predis
│   ├── RainLoop          # 核心運作程式碼
│   ├── SabreForRainLoop
│   ├── spyc
│   └── tmhOAuth
├── localization          # 語系相關
├── resources
└── templates             # html 樣板
```
---
**Release**

1. 版本號 `/data/Version`
2. 壓縮 `sh makedeb.sh`
3. 壓縮擋 `/build/dist`

---
**Encrypt**

1. Openpgp 加密 [Openpgp.js](https://github.com/openpgpjs/openpgpjs)
2. 不支援 S/MIME

**build**
1. gulp
2. webpack

使用 [knockout.js](https://knockoutjs.com/documentation/introduction.html) 


## Back - End

### Request 處理流程

例如:  `/?/AJAX/&q[]=/value_1/message/&q[]=/value_2`
系統接收到需求時，會先拆解/放進 aPaths 變數中
第一個值為系統函式`ServiceAction.php`
接著會從 Post值 或 Get值 中找出要執行的函式`Action.php`
在這個例子裡，系統會先執行 ServiceAjax 處理相關資訊後， 接著執行 DoMessage，讀取郵件資訊

---
### Hooks

專案中有很多已經定義好的 Hook 。

---
### Plugin


---
### HTTP Cache

Http Header Cache
頁面定時發送請求讀取郵件，每次取完一封郵件會紀錄 Cache 資料，下次讀取相同郵件時，檢查紀錄並回傳 304 訊息，告訴瀏覽器直接抓 Cache 資料。

注意: Http headers: request url: 可以直接讀資料 (expire-time: 3600;)

No-Cache: 參考 \MailSo\Base\Http\ServerNoCache ，在 http header 加上 no-cache, no-store.

---
### File Name Hash

[資料](https://medium.com/eonian-technologies/file-name-hashing-creating-a-hashed-directory-structure-eabb03aa4091)

**TL;DR**
本機資料儲存方式 - 利用 Hash 值，切成多個小片段的資料夾

使用方式
\MailSo\Cache\Drivers\File::NewInstance(目標資料夾);

* 存檔
    ->Set(StrValToHash, Content);
* 讀檔
    ->Get(StrValToHash);

---
### Auth

#### Login
登入時會產生一個 rlsession 及 rlspecauth，rlspecauth 為 rlsession 加一些使用者資訊加密組合而成，每步動作(doFolders, doMessageList ... )都需要將 rlspecauth 解密，登入 IMAP 取值

#### XToken
防止 Csrf 跨網站攻擊。首先會先產生一組 rltoken 存到 cookies，另外會給一組加了 SALT 的 rltoken (XToken)藏在 `<form>` 中。每次進行POST時，會將 rltoken 和 XToken 做驗證。避免沒有 rltoken 的其他網站可以進行 POST。


**取信 (doMessage)**
* Cookies
    * rlsession

* URL
    * domain/?/Ajax/&q[]=/`SpecAuth`/Message/&q[]=/`MailCode`
```php
MailCode = Base64.urlsafe_encode([
		sFolderFullNameRaw,
		iUid,
		AppStore.projectHash(),
		AppStore.threadsAllowed() && SettingsStore.useThreads() ? '1' : '0'
	].join(String.fromCharCode(0)))
    
必要 sFolderFullNameRaw, iUid
後兩個任意值即可
```

### 內建 API
1. 測試連線 `http://rainloop/?/Ping`
2. [SSO](https://github.com/RainLoop/rainloop-webmail/wiki/SSO-example)
3. 外部連結登入(`allow_external_login = On`) `http://rainloop/?/ExternalLogin&Email=$Email&Password=$Password`
4. 您的瀏覽器過期了
`http://rainloop/?/BadBrowser`
5. JavaScript 需要啟用提示頁面
`http://rainloop/?/NoScript`
6. Cookie 需要啟用提示頁面
`http://rainloop/?/NoCookie`
7. 取得 APPDATA
`http://rainloop/?/AppData`

#### Settings
1. `app/templates/Views/` 建立 template 樣板
2. `dev/Setting/` 建立給 template 值的 js
3. `dev/Screen/.../Settings.js`, `Enums.js`, `Capa.php`, `Action.php::Capa`, `Application.php` 修改值讓介面顯示

### PdoAbstract
getPdoAccessData : 設置連接資料庫時需要的資料
getPDO : 連接資料庫後return
prepareAndExecute : 準備執行的sql語句, aParams替換sSql內的值後執行該語句
quoteValue : 在sValue前後添加單引號
initSystemTables : 如果資料庫缺少一些table時會添加

### IMAP function
Connect : 如支援STARTTLS, 則使用STARTTLS鏈接
Login : 檢查支援的登入方式進行登入 
LoginWithXOauth2 : 使用AUTH=XOAUTH2方式登入
Logout : 登出
FOrceCloseConnection : 斷開鏈接
IsLoggined : 檢查登入狀態
IsSelected : 檢查是否已經選擇文件夾
Capability : 查看支援的功能
IsSupported : 檢查是否支援(sExtentionName)功能
GetNamespace : 取得imap名稱空間
Noop : 防止鏈接中斷
FolderCreate : 新建資料夾
FolderDelete : 刪除資料夾
FolderSubscribe : 顯示資料夾
FolderUnSubscribe : 隱藏資料夾
FolderRename :　更改資料夾名稱
getStatusFolderInformation : 取得資料夾狀態(Message, UidNext, Unseen, Highestmodseq)
FolderStatus : 取得狀態(呼叫getStatusFolderInformation)
getFoldersFromResult : 取得資料夾名稱和Flag
specificFolderList : 取得所有資料夾或子資料夾名稱(呼叫getFoldersFromResult)
FolderList :　取得所有資料夾名稱與Flag(呼叫specificFolderList)
FolderSubscribeList :　取得子資料夾名稱與Flag(呼叫specificFolderList)
FolderStatusList :　取得所有資料夾狀態(呼叫specificFolderList)
initCurrentFolderInformation :　取得資料夾的狀態並設定
selectOrExamineFolder : 選擇或檢查資料夾的狀態
FolderSelect :　根據(sFolderName)選擇並取得資料夾狀態(呼叫selectOrExamineFolder)
FolderExamine : 根據(sFolderName)取得資料夾狀態(呼叫selectOrExamineFolder)
FolderUnSelect : 取消已經選擇的資料夾
Fetch : 讀取信件(aInputFetchItems : 要讀取的內容, $sIndexRange : 讀取的範圍, $bIndexIsUid : 要讀取的Uid)
Quota : 取得容量與信件數量
MessageSimpleSort : 取得排序後的UID
simpleESearchOrESortHelper : 
MessageSimpleESearch : 
MessageSimpleESort : 
findLastResponse : 找到最後的回應
MessageSimpleSearch : 根據(sSearchCriterias, bReturnUid, sCharset)搜索信息
validateThreadItem : 檢查(aValue)是數字還是陣列
MessageSimpleThread : 根據(sSearchCriterias, bReturnUid, sCharset)取得資料
MessageCopy : 複製信件(可用來移動信件)
MessageMove : 移動信件
MessageExpunge : 刪除信件
MessageStoreFlag : 在信件上添加標記(seen/unseen/delete...)
MessageAppendStream : 上傳信件到資料夾(如草稿, 已發信件)
FolderCurrentInformation : return資料夾狀態
SendRequest : 把指令傳到Server上執行(呼叫sendRaw執行指令)
secureRequestParams : 把密碼替換成*
SendRequestWithCheck : 把指令傳到Server上執行, 並檢查回應是否正確(呼叫SendRequest, parseResponseWithValidation)
GetLastResponse : 取得最後的回應
validateResponse : 檢查(aResult)
parseResponse : 分析回應並return(呼叫partialParseResponseBranch)
parseResponseWithValidation : 分析回應並return, 檢查回應(呼叫validateResponse, parseResponse)
initCapabilityImapResponse : 把所有支援的功能字串設置爲大寫
partialParseResponseBranch : 分析回應
partialResponseLiteralCallbackCallable : 
prepearParamLine : 把(aParams)組合成字串
getNewTag : return新的Tag
getCurrentTag : return當前的Tag
EscapeString : return替換後的sStringForEscape
getLogName : return 字串('IMAP')
SetLogger : 設定Log
TestSetValues : 
TestParseResponseWithValidationProxy : 