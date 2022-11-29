g0v github 歷程整理器
=====================

為了製作 [g0v 成就系統](https://badge.g0v.tw)，這邊程式處理爬下 [g0v github](https://github.com/g0v) 的所有 repository ，並分析其 commit log ，以得出 [成就系統需要資料格式](https://g0v.hackmd.io/egkNjY94QfqC8DlN1AzM-g?both#%E8%B3%87%E6%96%99%E6%A0%BC%E5%BC%8F%E8%A6%8F%E5%8A%83)

使用方式
--------
- php crawl-list.php > repo.csv  # 抓取 repo 列表
- php check-repo.php {repo} # 將 {repo} 抓下來，並且匯出 commit log
- php check-all-repo.php # 裡用 check-repo.php ，將 repo.csv 裡面所有的 repo 結果都寫入 outputs/{repo}.csv
  


