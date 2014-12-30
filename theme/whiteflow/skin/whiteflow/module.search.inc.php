<div id="search">
<form action="<?php echo $script ?>?cmd=search" method="get">
<p>
<input type="hidden" name="cmd" value="search" />
<input type="text" name="word" size="13" value="検索キーワードを入力してください" tabindex="10" id="keyword" onfocus="if (value == '検索キーワードを入力してください') { value = ''; }" onblur="if (value == '') { value = '検索キーワードを入力してください'; }"  />
<input type="hidden" name="type" value="AND" tabindex="20" />
<input type="submit" value="検索" id="go-search" tabindex="30" />
</p>
</form>
</div>
<br style="clear: left" />