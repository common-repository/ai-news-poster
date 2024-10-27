jQuery(document).ready(function($) {
  let tableCount = 0;
  $('.generate-articles-button').click(async function() {
    $(this).prop('disabled', true).text('Processing...');
    $('.ainewsposter-container .loader').show();
    const currentTableCount = tableCount++; 
    try {
      const fetchedArticles = await fetchArticles();
      if(!Array.isArray(fetchedArticles.data)){
        alert("No articles found for the current configuration.");
        return;
      }
      createArticlesTable(fetchedArticles.data, currentTableCount);

      for (let i = 0; i < fetchedArticles.data.length; i++) {
        updateArticleStatus(currentTableCount, i, 'Checking for duplicates...', 'loading');
        const duplicatePosts = await checkForDuplicatePosts(fetchedArticles.data[i]);

        if (duplicatePosts['success']) {
          updateArticleStatus(currentTableCount, i, 'Retrieving content...', 'loading');
          var processedArticle;
          try{
            processedArticle = await processArticle(fetchedArticles.data[i]);
          } catch (error) {
            updateArticleStatus(currentTableCount, i, 'Failed: ' + error, 'error');
            continue;
          }
          if (processedArticle['success'] && processedArticle['data']['content'].length > 100) {
            updateArticleStatus(currentTableCount, i, 'Rewriting/Summarizing...', 'loading');
            var generatedArticle;
            try{
              generatedArticle = await generateArticle(processedArticle);
            } catch (error) {
              updateArticleStatus(currentTableCount, i, 'Failed: Likely Timeout while rewriting. This can be due to PHP\'s max_executiont_time configuration or your web server configuration. If the issue persists, please contact us, we can help.', 'error');
              continue;
            }

            if (!generatedArticle['success']) {
              updateArticleStatus(currentTableCount, i, 'Failed: ' + generatedArticle['data'], 'error');
            } else {
              updateArticleStatus(currentTableCount, i, 'Completed', 'success', generatedArticle['data']['post_id']);
            }
          } else {
            if (processedArticle['data'] == 'usage_limit_exceeded') {
             updateArticleStatus(currentTableCount, i, 'PagePixels Usage Limit reached.<br /><a href="https://pagepixels.com/app/billing/plan" target="_blank" class="button button-primary ainewsposter-save-button margin-top-only">Upgrade Account</a>', 'error');
            }else{
              updateArticleStatus(currentTableCount, i, 'Failed: Content Retrieved is too short', 'error');
            }
          }
        } else {
          updateArticleStatus(currentTableCount, i, 'Failed: Duplicate Post', 'error', duplicatePosts['data']);
        }
      }
    } catch (error) {
      console.error("Error: ", error);
    } finally {
      $('.ainewsposter-container .loader').hide();
      $(this).prop('disabled', false).text('Generate Articles');
    }
  });

  function createArticlesTable(articles, tableIndex) {
    let tableHtml = `<table class="articles-table" id="articles-table-${tableIndex}"><thead><tr><th>Article Title</th><th>Article URL</th><th class='status-th'>Status</th></tr></thead><tbody>`;

    articles.forEach((article, index) => {
      tableHtml += `<tr class="article-row">
        <td class="article-title">${article.name}</td>
        <td class="article-url"><a href="${article.url}" target="_blank">${article.url}</a></td>
        <td class="article-status" id="article-status-${tableIndex}-${index}">Waiting to process</td>
      </tr>`;
    });

    tableHtml += '</tbody></table>';
    $('#tab-generate-articles').append(tableHtml);
  }

  function updateArticleStatus(tableIndex, articleIndex, status, type, postId=null) {
    let statusHtml = '';

    if (type === 'loading') {
      statusHtml = `<div class="spinning-loader"></div> ${status}`;
    } else if (type === 'error') {
      statusHtml = `<span class='error'>${status}</span>`;
      if(postId){
        statusHtml += `<br />${createEditPostLink(postId)}`;
      }
    } else if (type === 'success') {
      statusHtml = `Completed<br />${createEditPostLink(postId)}`;
    }

    $(`#article-status-${tableIndex}-${articleIndex}`).html(statusHtml);
  }

  function createEditPostLink(postId) {
    return `<a href="post.php?post=${postId}&action=edit" target="_blank" class='edit-post-button'>Edit Post</a>`;
  }

  // Define fetchArticles, processArticle, and generateArticle as async functions
  const fetchArticles = async () => {
    return $.ajax({
      url: ainewsposter_ajax_obj.ajax_url,
      type: 'POST',
      data: {
        'action': 'ainewsposter_fetch_articles',
        'nonce': ainewsposter_ajax_obj.nonce
      }
    });
  };

  const checkForDuplicatePosts = async (article) => {
    return $.ajax({
      url: ainewsposter_ajax_obj.ajax_url,
      type: 'POST',
      data: {
        'action': 'ainewsposter_check_for_duplicate_posts',
        'article': article,
        'nonce': ainewsposter_ajax_obj.nonce
      }
    });
  };

  const processArticle = async (article) => {
    return $.ajax({
      url: ainewsposter_ajax_obj.ajax_url,
      type: 'POST',
      data: {
        'action': 'ainewsposter_process_article',
        'article': article,
        'nonce': ainewsposter_ajax_obj.nonce
      }
    });
  };

  const generateArticle = async (processedArticle) => {
    return $.ajax({
      url: ainewsposter_ajax_obj.ajax_url,
      type: 'POST',
      data: {
        'action': 'ainewsposter_generate_article',
        'article': processedArticle,
        'nonce': ainewsposter_ajax_obj.nonce
      }
    });
  };

});
