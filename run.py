#!python3

import json as Json
import re as Regexp
import sys as Sys
from seleniumbase import SB
from selenium.webdriver.common.by import By

if len(Sys.argv) < 2: throw Exception('Usage: ./run.py <skuID>')

skuID = Sys.argv[1]
url = 'https://www.ozon.ru/api/entrypoint-api.bx/page/json/v2?url=%%2Fproduct%%2F%d%%2F%%3Flayout_container%%3Dreviewshelfpaginator&__rr=1&abt_att=1'
rx_match = Regexp.compile('^webListReviews-[0-9]+-reviewshelfpaginator-[0-9]+$')

"""
Получить комментарии по товару по его артикулу
"""
def get_comments(skuID):
	results = []

	with SB(test=False, uc=True, headless2=True, undetectable=True) as sb:
		sb.open(url % (skuID))

		json_data = sb.find_element( By.CSS_SELECTOR , 'pre' ).get_attribute('textContent')

		states = Json.loads(json_data)['widgetStates']
		states = {k: v for k, v in states.items() if rx_match.match(k)}

		if not states:
			raise Exception('No comments')

		for comment in states.values():
			try :
				results.append(Json.loads(comment))
			except exception:
				raise exception

		return results

"""
Тестирование
"""
print(get_comments(skuID))