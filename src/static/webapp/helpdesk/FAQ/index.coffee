$ ->
  currentCategory = {}
  categories = []

  renderCategories = ->
    for category in categories
      categoryJQuery = $ '<div class="category"></div>'
      categoryJQuery.text category.name
      if currentCategory.name is category.name
        categoryJQuery.addClass 'activated'
      $('.categories-wrapper').append categoryJQuery

  renderContent = ->
    contentWrapper = $ '.content-wrapper'
    categoryQuestions = ''
    for category in categories
      categoryQuestions = '<div class="category-questions">
                            <div id="' + category.id + '" class="category">
                              ' + category.name + '
                            </div>
                            <div class="questions-wrapper">'
      for question in category.questions
        question.answer = question.answer.replace(/\n/g, '<br/>')
        categoryQuestions += '<div class="question">
                                <div class="question-title">' + question.question + '</div>
                                <div class="question-answer">' + question.answer + '</div>
                              </div>'

      categoryQuestions +=  '</div>
                           </div>'
      contentWrapper.append categoryQuestions

  hideCategorySubMenu = ->
    $('.categories-wrapper').hide()
    $('.current-category-wrapper').removeClass 'hasSubMenu'
    $('.categories-wrapper > .category').remove()
    $('.mask').hide()

  showCategorySubMenu = ->
    $('.categories-wrapper').show()
    $('.current-category-wrapper').addClass 'hasSubMenu'
    $('.mask').show()
    renderCategories()

  setCurrentCategory = (category) ->
    currentCategory = category
    $('.current-category').text currentCategory.name
    $('.img-arrow').show() if categories.length > 1

  getCategoryByName = (name) ->
    for category in categories
      if category.name is name
        return category

  checkCurrentCategory = ->
    # Beacuse the height of fixed category at the top of window is 41,
    # the listener is watching the DOM at point(0, 42).
    topElement = document.elementFromPoint 0, 42
    if $(topElement).hasClass 'questions-wrapper'
      categoryId = $(topElement).siblings('.category').attr('id')
      for category in categories
        if category.id is categoryId
          setCurrentCategory category
          break

  formatData = (categories) ->
    for category in categories
      if category.isDefault
        category.name = '默认分类'
        break

  init = ->
    $('title').text '常见问题解答'

    category = util.queryMap.category
    accountId = util.queryMap.accountId
    queryData =
      accountId: accountId
      category: if category then category else ''
    queryUrl = '/helpdesk/faq/get-faqs'
    rest.get queryUrl, queryData, (data) ->
      if data
        categories = if $.isArray data then data else [data]
        formatData(categories)
        setCurrentCategory categories[0]
        renderContent()

    $('.content-wrapper').on 'click', '.question-title', (event) ->
      if $(this).hasClass 'activated'
        $(this).siblings('.question-answer').hide()
        $(this).removeClass 'activated'
      else
        $(this).siblings('.question-answer').show()
        $(this).addClass 'activated'

    $('.current-category-wrapper').on 'click', (event) ->
      return if categories.length is 1
      if $(this).hasClass 'hasSubMenu'
        hideCategorySubMenu()
      else
        showCategorySubMenu()

    $('.mask').on 'click', (event) ->
      hideCategorySubMenu()

    $('.categories-wrapper').on 'click', '.category', (event) ->
      category = getCategoryByName $(this).text()
      setCurrentCategory category
      hideCategorySubMenu()
      window.location.href = '#' + category.id
      checkCurrentCategory()

    $('.content-wrapper').on 'scroll', util.throttle checkCurrentCategory, 100, true

  init()
