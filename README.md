SimpleJob
=========
flockとincronを前提としたシンプルなジョブキューで実現する非同期処理用プラグインです。

# Example

- SimpleJobをプラグインとして設置・ロードします
- Config/Schema/schema.php を使ってSimpleJobモデル用のDBのテーブルsimple_jobsを用意します

```
./Console/cake schema create --plugin SimpleJob
```

- TMP以下にディレクトリを追加してパーミッションを変更します

```
mkdir tmp/simple_job_trigger; 
chmod 777 tmp/simple_job_trigger
```  

- SimpleJobモデルをbakeなどで生成しSimpleJob.SimpleJobビヘイビアを使うよう設定します

- incrontabに以下のような設定を追加します

```
/(path to APP)/tmp/simple_job_trigger IN_CREATE /(path to APP)/Console/cake -root /(path to ROOT) SimpleJob.worker
```  

- Console/Command/Taskにジョブで実行する処理を実装したTaskクラスを追加します

- SimpleJobモデルのenqueueメソッドで追加したTaskクラスを実行するジョブを登録します
