#!python3

import sys as Sys

"""
Получить номер артикула из STDIN
"""
if (len(Sys.argv) < 2) or not Sys.argv[1].isdigit():
	raise Exception('Usage: "%s" "%s" <skuID>' % (Sys.executable, Sys.argv[0]))

id_sku = Sys.argv[1]

import json as Json
import re as Regexp
import urllib.parse as UrlParse
from seleniumbase import SB
from selenium.webdriver.common.by import By

"""
Сообщение в STDERR
"""
def warn(msg):
	return print("\n<<STDERR:%s\n" % msg, Sys.stderr)

"""
Получить JSON страницы
"""
def get_page_data(id_sku):
	url_start = 'https://www.ozon.ru/api/entrypoint-api.bx/page/json/v2?url='
	url_part = UrlParse.quote_plus('/product/' + UrlParse.quote_plus(id_sku) + '/?layout_container=reviewshelfpaginator')
	url = url_start + url_part + '&__rr=1&abt_att=1'

	with SB(test=False, uc=True, headless2=True, undetectable=True) as sb:
		while True:
			warn(url)

			sb.open(url)

			page_data = sb.find_element(By.TAG_NAME, 'pre').get_attribute('textContent')
			json_data = Json.loads(page_data)

			yield json_data['widgetStates']

			url = url_start + UrlParse.quote_plus(json_data['nextPage'])

"""
Получить комментарии о товаре по его артикулу
"""
def get_page_comments(id_sku):
	rx_match = Regexp.compile('^webListReviews-[0-9]+-reviewshelfpaginator-[0-9]+$')

	for page_data in get_page_data(id_sku):
		for comment_key, comment in page_data.items():
			if rx_match.match(comment_key):
				for review in Json.loads(comment)['reviews']:
					yield review['content']['comment']

"""
Тестирование
"""
for page_comments in get_page_comments(id_sku):
	print(Json.dumps(page_comments) + "\n")