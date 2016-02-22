# 项目中使用Git的规范

* Gitflow 总览

![Gitflow](http://vdemo.qiniudn.com/gitflow.svg)

* Feature和Bug分支模型

![Feature Branch](http://vdemo.qiniudn.com/feature-branch.svg)

## 基本规范

1. 项目中有两个固定分支master和develop
2. 开发本地只克隆develop分支, 只有需要release或者hotfix时候才会在本地克隆master代码来进行合并， **master分支始终处于被动合并状态，不能向master上主动提交develop分支上没有合并的代码**
3. 所有开发时新的feature或者新的bug都只能通过发 **merge request** 由 **review的人合并到develop分支上**
4. 项目发布时从 **develop** 上checkout分支， **hotfix** 从master上checkout分支，完成后都要合并回 **两个分支(master, develop)** 上


## 项目代码合并流程

###  1. 克隆远端的develop分支

```bash
git clone -b develop <remote-repository-name>
```

###  2. 创建新的分支完成task

开始实现一个feature或者解决一个bug之前，首先切换到develop分支上

```bash
git checkout develop
```

创建feature或者bug的分支,并且切换到该分支

```bash
git checkout -b <feature/bug-branch-name> develop
```

**分支名称需要遵循命名规:** 

* **feature:** 格式遵守`feature-storyID-moduleName`， 例如`feature-4213-member`
* **bug:** 格式遵守`bug-bugID-moduleName`， 例如`bug-4216-product`

###  3. 完成开发的工作

修改一些文件，添加修改的文件到暂存区(staging area)

```bash
git add .
```

将修改后的文件提交到本地的版本库中

```bash
git commit -am 'Add a new feature'
```

你可以在本地多次提交，可以任意写你每个commit的信息(最好也同时遵守[规范](https://github.com/ajoslin/conventional-changelog/blob/master/conventions/angular.md))

### 4. 独立分支上合并提交

这里 **特别注意** ，使用rebase命令将你在自己分支上的多次提交合并成一次提交，同时合并develop分支

```bash
git checkout <feature／bug-branch-name>
git rebase -i develop
```

**Notice:** 使用rebase命令将你在feature／bug分支上的多次提交合并成一次， 保证提交的原子性。其中`-i` 参数会提供交互的方式引导，一般你会看到这样的编辑界面

```bash
pick ff76694 feat(venue): add static page for wechat audience module
pick 8e49657 feat(venue): finish the frontend controller for wechat audience module
pick 69f1a3e feat(venue): finish the backend controller for wechat audience module
# Rebase d879706..8e49657 onto d879706
#
# Commands:
#  p, pick = use commit
#  r, reword = use commit, but edit the commit message
#  e, edit = use commit, but stop for amending
#  s, squash = use commit, but meld into previous commit
#  f, fixup = like "squash", but discard this commit's log message
#  x, exec = run command (the rest of the line) using shell
#
# These lines can be re-ordered; they are executed from top to bottom.
#
# If you remove a line here THAT COMMIT WILL BE LOST.
#
# However, if you remove everything, the rebase will be aborted.
#
# Note that empty commits are commented out
```

下面的注释中Command有详细的解释，一般将你要合并的多个提交前面的pick改为squash（如果有多个commit除了第一个是pick其他都是squash），对于我们的示例，修改为

```bash
pick ff76694 feat(venue): add static page for wechat audience module
squash 8e49657 feat(venue): finish the frontend controller for wechat audience module
squash 69f1a3e feat(venue): finish the backend controller for wechat audience module
# Rebase d879706..8e49657 onto d879706
#...
```

保存文件退出编辑，正常情况下这样就完事了，但是如果develop分支上有新的提交和你的工作分支提交发生冲突，就会看到下面的情况

```bash
error: could not apply ff76694... feat(venue): add static page for wechat audience module

When you have resolved this problem, run "git rebase --continue".
If you prefer to skip this patch, run "git rebase --skip" instead.
To check out the original branch and stop rebasing, run "git rebase --abort".
Could not apply ff76694624019140e05cd9d443aa547e62c5c24b... add line 3
```

编辑冲突文件（冲突文件中可能不会列出你在当前分支上所有的改动，只会标出冲突部分），选择需要的部分，保存文件

```bash
git add <modified files>
git rebase --continue
```

之后你会看到类似这样的提示

```bash
feat(venue): add static page for wechat audience module

# Please enter the commit message for your changes. Lines starting
# with '#' will be ignored, and an empty message aborts the commit.
# rebase in progress; onto d879706
# You are currently rebasing branch 'feature' on 'd879706'.
#
# Changes to be committed:
#       modified:   test.txt
#
```

这里是让你编辑你第一次提交的comment，更改comment内容后保存退出，紧接着会提示你编辑合并之后commit的comment，你在上一次添加的comment会合并进来，你看到的会是这样的

```bash
# This is a combination of 2 commits.
# The first commit's message is:

# feat(venue): add static page for wechat audience module
# -----change to----->

feat(venue): finish wechat audience module

# This is the 2nd commit message:

# feat(venue): finish the frontend controller for wechat audience module

# This is the 3nd commit message:

# feat(venue): finish the backend controller for wechat audience module

# Please enter the commit message for your changes. Lines starting
# with '#' will be ignored, and an empty message aborts the commit.
# rebase in progress; onto d879706
# You are currently editing a commit while rebasing branch 'feature' on 'd879706'.
#
# Changes to be committed:
#       modified:   test.txt
#
```

**去掉多余的comment内容(像示例中一样，只保留一条comment)**，保存退出。将工作分支上的多次提交合并成一次提交，更详细的说明看[这里](http://gitready.com/advanced/2009/02/10/squashing-commits-with-rebase.html)

### ５. 合并到develop分支

在本地解决合并之后的冲突，同时仅保留feature本身提交的comment(去除在merge中自动生成的提交信息)，解决冲突后将分支提交到中央仓库

```bash
git push origin <feature／bug-branch-name>
```

在gitlab上手动创建一个新的merge request，指定source branch和target branch，点击`Compare branches and continue`按钮，指定assignee为可以为你review的项目相关负责人或者直接给项目的manager，使用提交的comment作为merge request的title，点击创建按钮创建merge request．
在 **完成code review没有问题之后** 由assignee来删除用于merge的branch同时将merge request合并回develop分支。

如果在review过程中有问题需要修改或者在发merge request过程中有人提交代码，该merge就不能被直接合并。

在本地重新修改并且本地提交修改，再次rebase develop解决冲突，将commit再次合并成一个，强制提交到你的独立分支，重新发merge request

```
git push origin <feature／bug-branch-name> -f
```

## 准备release

### 1. 创建release分支

```bash
git checkout -b <release-branch-name> develop
```

### 2. 完成release的相关工作 - Update

```bash
git add .
git commit -am 'Add release related code1'
...
git commit -am 'Add release related code2'
git rebase -i HEAD~3
```

HEAD~3代表release的分支头指针之前连续三次的提交，这里通过rebase重写历史，将release分支上的多次提交合并成单次提交，方便master和develop进行合并

### 3. 将release分支合并到master和develop分支，并且同步到中央版本库 - use merge here

```bash
git checkout <release-branch-name>
git rebase master
git checkout master
git merge <release-branch-name>
git push
git checkout <release-branch-name>
git rebase develop
git checkout develop
git merge <release-branch-name>
git push
git branch -d <release-branch-name>
```

### 4. 在master上添加tag，并且同步到中央服务器

```bash
git tag -a <version-number> -m 'Create 0.1 version release' master
git push --tags
```

## 创建hotfix

### 1. 从master分支上创建一个hotfix的新分支

```bash
git checkout -b <hotifx-branch-name> master
```

### 2. 修复问题

```bash
git add .
git commit -am 'Fix bug #636 use code1'
...
git commit -am 'Fix bug #636 use code2'
git rebase -i HEAD~3
```

HEAD~3代表hotfix的分支头指针之前连续三次的提交，这里通过rebase重写历史，将release分支上的多次提交合并成单次提交，方便master和develop进行合并

### 3. 将hotfix分支合并到master和develop分支，并且同步到中央版本库 - use merge

```bash
git checkout <hotifx-branch-name>
git rebase master
git checkout master
git merge <hotifx-branch-name>
git push
git checkout <hotifx-branch-name>
git rebase develop
git checkout develop
git merge <hotifx-branch-name>d
git push
git branch -d <hotifx-branch-name>
```

参考
--------------------------
[Git 最佳实践](https://www.atlassian.com/git/)

[快速开始](http://rogerdudler.github.io/git-guide/)

[Git文档](http://git-scm.com/book/en)

[我的Github工作流](https://github.com/vincenthou/vincenthou.github.io/issues/1)
