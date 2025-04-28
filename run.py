#!python3

import json as Json
import re as Regexp
import sys as Sys
from seleniumbase import SB
from selenium.webdriver.common.by import By

if len(Sys.argv) < 2: raise Exception('Usage: ./run.py <skuID>')

sku_id = Sys.argv[1]
url_pattern = 'https://www.ozon.ru/api/entrypoint-api.bx/page/json/v2?url=%%2Fproduct%%2F%d%%2F%%3Flayout_container%%3Dreviewshelfpaginator&__rr=1&abt_att=1'
rx_match = Regexp.compile('^webListReviews-[0-9]+-reviewshelfpaginator-[0-9]+$')

"""
Получить JSON страницы
"""
def get_page_data(sku_id):
	with SB(test=False, uc=True, headless2=True, undetectable=True) as sb:
		sb.open(url_pattern % (int(sku_id)))

		return sb.find_element(By.CSS_SELECTOR, 'pre').get_attribute('textContent')

"""
Получить комментарии по товару по его артикулу
"""
def get_comments(sku_id):
	results = []

	for comment_key, comment in Json.loads(get_page_data(sku_id))['widgetStates'].items():
		if not rx_match.match(comment_key):
			continue

		for review in Json.loads(comment)['reviews']:
			results.append(review['content']['comment'])

	return results

"""
Тестирование
"""
comments = get_comments(sku_id)

with open('out.txt', 'w') as fh:
	Json.dump(comments, fh)