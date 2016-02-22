## Install

1. For full documentation visit [mkdocs.org](http://mkdocs.org).
2. Install MkDocs
    * Use Alibaba Open Source [Ali-OSM](http://mirrors.aliyun.com/help/pypi) for pip
    * `sudo pip install mkdocs`

## Commands

* `mkdocs new [dir-name]` - Create a new project.
* `mkdocs serve` - Start the live-reloading docs server.
* `mkdocs build` - Build the documentation site.
* `mkdocs help` - Print this help message.

## Project layout

    mkdocs.yml    # The configuration file.
    docs/
        index.md  # The documentation homepage.
        about.md  # The author to write this docs
        directives
            ...       # directive md files
        filters
            ...       # filter md files
        services
            ...       # service md files

## Build
1. run `mkdocs build`
2. run `mkdocs serve`, then visit http://127.0.0.1:8000/
3. add your pages in docs folder, then modify `mkdocs.yml` file to add your page

## References
1. [ngnice directive api](http://docs.ngnice.com/api/ng/directive)