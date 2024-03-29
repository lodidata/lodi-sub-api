#添加AT游戏配置
INSERT INTO `game_api` (`id`, `type`, `name`, `lobby`, `cagent`, `key`, `loginUrl`, `orderUrl`, `apiUrl`, `update_at`) VALUES (80, 'AT', 'AT电子', '1457', 'tgtg','aa123321', '', 'https://api-stage.at888888.com/service/', 'https://api-stage.at888888.com/service/', '2022-03-21 17:12:04');
#添加AT游戏分类
INSERT INTO `game_menu` (`id`, `pid`, `type`, `name`, `alias`, `rename`,`status`,`switch`, `across_status`) VALUES (88, 4, 'AT', 'ATSLOT', 'AT', 'AT电子', 'enabled','enabled', 'enabled'),
(89, 22, 'ATBY', 'ATFH', 'AT', 'AT捕鱼', 'enabled', 'enabled', 'enabled'),
(90, 19, 'ATJJ', 'ATARCADE', 'AT', 'AT街机', 'enabled','enabled', 'enabled');

#添加AT游戏
INSERT INTO `game_3th` (`id`, `kind_id`, `game_id`, `game_name`, `rename`, `type`, `alias`, `qp_img`, `game_img`, `sort`, `created`, `updated`, `maintain`, `is_hot`, `status`, `across_sort`, `across_status`, `extension_img`, `is_freespin`) VALUES 
(4734, 'cm00001', 88, 'CHINA VOYAGE', '中国游', 'slot', 'CHINAVOYAGE', NULL, NULL, 5, '2022-03-22 09:08:53', '2022-03-22 09:08:53', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4735, 'cm00002', 88, 'FA FA FA', '发发发', 'slot', 'FAFAFA', NULL, NULL, 6, '2022-03-22 09:08:54', '2022-03-22 09:08:54', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4736, 'cm00003', 88, 'OH MY RICH DEER', '一鹿发', 'slot', 'OHMYRICHDEER', NULL, NULL, 7, '2022-03-22 09:08:55', '2022-03-22 09:08:55', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4737, 'cm00004', 88, 'GREAT SWORD OF DRAGON', '亢龙锏', 'slot', 'GREATSWORDOFDRAGON', NULL, NULL, 8, '2022-03-22 09:08:56', '2022-03-22 09:08:56', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4738, 'cm00005', 88, 'CHINA EMPRESS', '五媚娘', 'slot', 'CHINAEMPRESS', NULL, NULL, 9, '2022-03-22 09:08:57', '2022-03-22 09:08:57', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4739, 'cm00006', 88, 'RUYI’S ROYAL LOVE', '万事如懿', 'slot', 'RUYI’SROYALLOVE', NULL, NULL, 10, '2022-03-22 09:08:58', '2022-03-22 09:08:58', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4740, 'cm00007', 88, ' DETECTIVE DEE', '迪仁杰', 'slot', 'DETECTIVEDEE', NULL, NULL, 11, '2022-03-22 09:09:00', '2022-03-22 09:09:00', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4741, 'cm00008', 88, 'DOUBLE SEVENTH FESTIVAL', '七夕', 'slot', 'DOUBLESEVENTHFESTIVAL', NULL, NULL, 12, '2022-03-22 09:09:01', '2022-03-22 09:09:01', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4742, 'cm00009', 88, 'GOLDEN EMPIRE', '黄金选择', 'slot', 'GOLDENEMPIRE', NULL, NULL, 13, '2022-03-22 09:09:02', '2022-03-22 09:09:02', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4743, 'cm00010', 88, 'ETERNITY OF DIAMOND', '钻石永恒', 'slot', 'ETERNITYOFDIAMOND', NULL, NULL, 14, '2022-03-22 09:09:03', '2022-03-22 09:09:03', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4744, 'cm00011', 88, ' CAI FU', '财富发发', 'slot', 'CAIFU', NULL, NULL, 15, '2022-03-22 09:09:04', '2022-03-22 09:09:04', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4745, 'cm00012', 88, 'HAPPY YEAR OF THE PIG', '猪事顺利', 'slot', 'HAPPYYEAROFTHEPIG', NULL, NULL, 16, '2022-03-22 09:09:05', '2022-03-22 09:09:05', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4746, 'cm00013', 88, 'GOLDEN PIG GOOD NEWS', '金猪报喜', 'slot', 'GOLDENPIGGOODNEWS', NULL, NULL, 17, '2022-03-22 09:09:06', '2022-03-22 09:09:06', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4747, 'cm00014', 88, 'MID AUTUMN FESTIVAL', '中秋佳节', 'slot', 'MIDAUTUMNFESTIVAL', NULL, NULL, 18, '2022-03-22 09:09:07', '2022-03-22 09:09:07', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4748, 'cm00015', 88, 'MONSTER HUNT', '捉妖记', 'slot', 'MONSTERHUNT', NULL, NULL, 19, '2022-03-22 09:09:08', '2022-03-22 09:09:08', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4749, 'cm00016', 88, 'GREAT EXPECTATIONS', '远大前程', 'slot', 'GREATEXPECTATIONS', NULL, NULL, 20, '2022-03-22 09:09:09', '2022-03-22 09:09:09', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4750, 'cm00017', 88, 'DETECTIVE CHINATOWN', '唐人街探案', 'slot', 'DETECTIVECHINATOWN', NULL, NULL, 21, '2022-03-22 09:09:10', '2022-03-22 09:09:10', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4751, 'cm00018', 88, 'TAOYUAN BROTHERHOOD', '桃园结义', 'slot', 'TAOYUANBROTHERHOOD', NULL, NULL, 22, '2022-03-22 09:09:11', '2022-03-22 09:09:11', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4752, 'cm00019', 88, 'BLOOMS OVER BLOOMS', '十里桃花', 'slot', 'BLOOMSOVERBLOOMS', NULL, NULL, 23, '2022-03-22 09:09:12', '2022-03-22 09:09:12', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4753, 'cm00020', 88, 'LIFE AFTER LIFE', '枕上发', 'slot', 'LIFEAFTERLIFE', NULL, NULL, 24, '2022-03-22 09:09:13', '2022-03-22 09:09:13', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4754, 'cm00021', 88, 'EMPRESSES IN THE PALACE', '媜嬛无双', 'slot', 'EMPRESSESINTHEPALACE', NULL, NULL, 25, '2022-03-22 09:09:14', '2022-03-22 09:09:14', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4755, 'cm00022', 88, 'Journey to the West', '西游记', 'slot', 'JourneytotheWest', NULL, NULL, 26, '2022-03-22 09:09:15', '2022-03-22 09:09:15', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4756, 'cm00023', 88, 'Amphawa Floating Market​', '安帕瓦水上市场', 'slot', 'AmphawaFloatingMarket​', NULL, NULL, 27, '2022-03-22 09:09:16', '2022-03-22 09:09:16', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4757, 'cm00026', 88, 'Fortune Strike', '我爱一条柴', 'slot', 'FortuneStrike', NULL, NULL, 28, '2022-03-22 09:09:17', '2022-03-22 09:09:17', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4758, 'cm00030', 88, 'Five Tiger Generals', '五虎将', 'slot', 'FiveTigerGenerals', NULL, NULL, 29, '2022-03-22 09:09:18', '2022-03-22 09:09:18', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4759, 'cm00031', 88, 'LEAD GENERALS', '官将首', 'slot', 'LEADGENERALS', NULL, NULL, 30, '2022-03-22 09:09:19', '2022-03-22 09:09:19', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4760, 'cm00032', 88, 'MAZU', '妈祖', 'slot', 'MAZU', NULL, NULL, 31, '2022-03-22 09:09:21', '2022-03-22 09:09:21', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4761, 'cm00033', 88, 'HOT CHONGQING', '火辣重庆', 'slot', 'HOTCHONGQING', NULL, NULL, 32, '2022-03-22 09:09:22', '2022-03-22 09:09:22', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4762, 'cm00034', 88, 'HOUYI\'S LEGEND', '后羿射日', 'slot', 'HOUYISLEGEND', NULL, NULL, 33, '2022-03-22 09:16:20', '2022-03-22 09:16:20', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4763, 'cm00035', 88, 'MARTIAL ART MASTER', '一代宗师', 'slot', 'MARTIALARTMASTER', NULL, NULL, 34, '2022-03-22 09:09:24', '2022-03-22 09:09:24', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4764, 'cm00038', 88, 'DESERT CAMEL', '沙漠骆驼', 'slot', 'DESERTCAMEL', NULL, NULL, 35, '2022-03-22 09:09:25', '2022-03-22 09:09:25', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4765, 'cm00040', 88, 'BACK HERE AGAIN', '浪子回頭', 'slot', 'BACKHEREAGAIN', NULL, NULL, 36, '2022-03-22 09:09:26', '2022-03-22 09:09:26', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4766, 'cm00042', 88, 'GATAO', '角头', 'slot', 'GATAO', NULL, NULL, 37, '2022-03-22 09:09:27', '2022-03-22 09:09:27', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4767, 'cm00046', 88, 'Fortune Bulls', '牛来运转', 'slot', 'FortuneBulls', NULL, NULL, 38, '2022-03-22 09:09:28', '2022-03-22 09:09:28', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4768, 'cm00055', 88, 'FORTUNE MOUSE​', '鼠来宝', 'slot', 'FORTUNEMOUSE​', NULL, NULL, 39, '2022-03-22 09:09:29', '2022-03-22 09:09:29', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4769, 'cm00056', 88, 'RICH MOUSE', '鼠年钱黏', 'slot', 'RICHMOUSE', NULL, NULL, 40, '2022-03-22 09:09:30', '2022-03-22 09:09:30', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4770, 'cm00063', 88, 'GIVE YOU MONEY', '给您钱', 'slot', 'GIVEYOUMONEY', NULL, NULL, 41, '2022-03-22 09:09:31', '2022-03-22 09:09:31', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4771, 'cm00067', 88, 'SIC BO PARTY', '骰豹蹦迪', 'slot', 'SICBOPARTY', NULL, NULL, 42, '2022-03-22 09:09:32', '2022-03-22 09:09:32', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4772, 'cm00074', 88, 'Getting Crazily Rich', '疯狂发发发', 'slot', 'GettingCrazilyRich', NULL, NULL, 43, '2022-03-22 09:09:33', '2022-03-22 09:09:33', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4773, 'cm00075', 88, 'China face changing', '中国变脸', 'slot', 'Chinafacechanging', NULL, NULL, 44, '2022-03-22 09:09:34', '2022-03-22 09:09:34', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4774, 'cm00076', 88, 'SICHUAN OPERA FACE CHANGING', '川剧变脸', 'slot', 'SICHUANOPERAFACECHANGING', NULL, NULL, 45, '2022-03-22 09:09:35', '2022-03-22 09:09:35', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4775, 'cm00083', 88, 'RATS BULLS TIGERS RABBITS', '鼠牛虎兔', 'slot', 'RATSBULLSTIGERSRABBITS', NULL, NULL, 46, '2022-03-22 09:09:36', '2022-03-22 09:09:36', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4778, 'coo0001', 88, 'Golden Dynasty', '黄金朝代', 'slot', 'GoldenDynasty', NULL, NULL, 49, '2022-03-22 09:09:40', '2022-03-22 09:09:40', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4781, 'coo0004', 88, 'LUBU', '真吕布无双', 'slot', 'LUBU', NULL, NULL, 52, '2022-03-22 09:09:43', '2022-03-22 09:09:43', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4782, 'coo0005', 88, 'HUGA', '野蛮世界', 'slot', 'HUGA', NULL, NULL, 53, '2022-03-22 09:09:44', '2022-03-22 09:09:44', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4783, 'coo0006', 88, 'THUNDER FENG SHEN', '雷电封神', 'slot', 'THUNDERFENGSHEN', NULL, NULL, 54, '2022-03-22 09:09:45', '2022-03-22 09:09:45', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4784, 'coo0007', 88, 'SUI HU HEROES', '水浒英雄', 'slot', 'SUIHUHEROES', NULL, NULL, 55, '2022-03-22 09:09:46', '2022-03-22 09:09:46', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4785, 'coo0008', 88, 'Fa Fa Fa', '发发发', 'slot', 'FaFaFa', NULL, NULL, 56, '2022-03-22 09:09:47', '2022-03-22 09:09:47', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4787, 'coo0010', 88, 'Peony Beauty', '醉红颜', 'slot', 'PeonyBeauty', NULL, NULL, 58, '2022-03-22 09:09:49', '2022-03-22 09:09:49', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4788, 'coo0011', 88, 'Hawaii', '热浪夏威夷', 'slot', 'Hawaii', NULL, NULL, 59, '2022-03-22 09:09:50', '2022-03-22 09:09:50', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4789, 'coo0012', 88, 'Football Champion', '冠军足球', 'slot', 'FootballChampion', NULL, NULL, 60, '2022-03-22 09:09:51', '2022-03-22 09:09:51', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4790, 'coo0013', 88, 'Sparta', '斯巴达', 'slot', 'Sparta', NULL, NULL, 61, '2022-03-22 09:09:52', '2022-03-22 09:09:52', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4791, 'coo0014', 88, 'Poker Slam', '朴克派对', 'slot', 'PokerSlam', NULL, NULL, 62, '2022-03-22 09:09:53', '2022-03-22 09:09:53', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4792, 'coo0015', 88, 'Fortune Goddess', '财宝天后', 'slot', 'FortuneGoddess', NULL, NULL, 63, '2022-03-22 09:09:54', '2022-03-22 09:09:54', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4793, 'coo0016', 88, 'Caishen FaFaFa 2', '财神发发发 2', 'slot', 'CaishenFaFaFa2', NULL, NULL, 64, '2022-03-22 09:09:55', '2022-03-22 09:09:55', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4794, 'coo0017', 88, 'Southern Queen', '南国女帝', 'slot', 'SouthernQueen', NULL, NULL, 65, '2022-03-22 09:09:56', '2022-03-22 09:09:56', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4795, 'coo0018', 88, 'Legend of Egypt', '埃及传奇', 'slot', 'LegendofEgypt', NULL, NULL, 66, '2022-03-22 09:09:57', '2022-03-22 09:09:57', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4796, 'coo0019', 88, 'EROS', '性感女神', 'slot', 'EROS', NULL, NULL, 67, '2022-03-22 09:09:58', '2022-03-22 09:09:58', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4797, 'coo0020', 88, 'Da Ji Da Li', '大吉大利', 'slot', 'DaJiDaLi', NULL, NULL, 68, '2022-03-22 09:10:00', '2022-03-22 09:10:00', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4798, 'coo0021', 88, 'White Lion Kingdom', '白狮王国', 'slot', 'WhiteLionKingdom', NULL, NULL, 69, '2022-03-22 09:10:01', '2022-03-22 09:10:01', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4799, 'coo0022', 88, 'Freeway King', '公路之王', 'slot', 'FreewayKing', NULL, NULL, 70, '2022-03-22 09:10:02', '2022-03-22 09:10:02', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4800, 'coo0023', 88, 'Caribbean Pirates', '海盗传奇', 'slot', 'CaribbeanPirates', NULL, NULL, 71, '2022-03-22 09:10:03', '2022-03-22 09:10:03', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4801, 'coo0024', 88, '5 Dragons Treasure', '五龙秘宝', 'slot', '5DragonsTreasure', NULL, NULL, 72, '2022-03-22 09:10:04', '2022-03-22 09:10:04', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4802, 'coo0025', 88, 'Monkey Warrior', '齐天大圣', 'slot', 'MonkeyWarrior', NULL, NULL, 73, '2022-03-22 09:10:05', '2022-03-22 09:10:05', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4803, 'coo0026', 88, 'Faraoh\'s Treasure', '法老传奇', 'slot', 'FaraohsTreasure', NULL, NULL, 74, '2022-03-22 09:16:31', '2022-03-22 09:16:31', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4804, 'coo0027', 88, 'Midnight Panther', '月下黑豹', 'slot', 'MidnightPanther', NULL, NULL, 75, '2022-03-22 09:10:07', '2022-03-22 09:10:07', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4805, 'coo0028', 88, 'Fortune Mahjong', '麻将来了', 'slot', 'FortuneMahjong', NULL, NULL, 76, '2022-03-22 09:10:08', '2022-03-22 09:10:08', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4806, 'coo0029', 88, 'Treasure Maya', '玛雅探险', 'slot', 'TreasureMaya', NULL, NULL, 77, '2022-03-22 09:10:09', '2022-03-22 09:10:09', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4807, 'coo0030', 88, 'Frozen Land', '冰雪奇景', 'slot', 'FrozenLand', NULL, NULL, 78, '2022-03-22 09:10:10', '2022-03-22 09:10:10', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4808, 'coo0031', 88, 'Ocean Blue', '深海世界', 'slot', 'OceanBlue', NULL, NULL, 79, '2022-03-22 09:10:11', '2022-03-22 09:10:11', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4809, 'coo0032', 88, 'African Adventure', '非洲草原', 'slot', 'AfricanAdventure', NULL, NULL, 80, '2022-03-22 09:10:12', '2022-03-22 09:10:12', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4810, 'coo0033', 88, 'Alice in Wonderland', '爱丽丝梦游', 'slot', 'AliceinWonderland', NULL, NULL, 81, '2022-03-22 09:10:13', '2022-03-22 09:10:13', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4811, 'coo0034', 88, 'Bear and Honey', '熊与蜂蜜', 'slot', 'BearandHoney', NULL, NULL, 82, '2022-03-22 09:10:14', '2022-03-22 09:10:14', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4812, 'coo0035', 88, 'Thai Sweet Girl', '泰国奇缘', 'slot', 'ThaiSweetGirl', NULL, NULL, 83, '2022-03-22 09:10:15', '2022-03-22 09:10:15', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4813, 'coo0036', 88, 'Taj Mahal', '泰姬玛哈', 'slot', 'TajMahal', NULL, NULL, 84, '2022-03-22 09:10:16', '2022-03-22 09:10:16', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4814, 'coo0037', 88, 'Roman Gladiator', '罗马战士', 'slot', 'RomanGladiator', NULL, NULL, 85, '2022-03-22 09:10:17', '2022-03-22 09:10:17', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4815, 'coo0038', 88, 'CaiShen\'s Fortune', '财气逼人', 'slot', 'CaiShensFortune', NULL, NULL, 86, '2022-03-22 09:16:37', '2022-03-22 09:16:37', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4824, 'coo0047', 88, 'Lion King', '獅子王', 'slot', 'LionKing', NULL, NULL, 95, '2022-03-22 09:10:28', '2022-03-22 09:10:28', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4825, 'coo0048', 88, 'Five Phoenix Legend', '五凤传说', 'slot', 'FivePhoenixLegend', NULL, NULL, 96, '2022-03-22 09:10:29', '2022-03-22 09:10:29', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4826, 'coo0049', 88, 'Fortune Tree', '吉祥如意', 'slot', 'FortuneTree', NULL, NULL, 97, '2022-03-22 09:10:30', '2022-03-22 09:10:30', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4827, 'coo0050', 88, 'Okinawa Beauties', '沖縄の女', 'slot', 'OkinawaBeauties', NULL, NULL, 98, '2022-03-22 09:10:31', '2022-03-22 09:10:31', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4830, 'coo0053', 88, 'Fortune India FIRE LINK', '富贵印度 火焰连线', 'slot', 'FortuneIndiaFIRELINK', NULL, NULL, 101, '2022-03-22 09:10:34', '2022-03-22 09:10:34', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4831, 'man0001', 88, 'Tokyo Sweeties', '东京甜心', 'slot', 'TokyoSweeties', NULL, NULL, 102, '2022-03-22 09:10:35', '2022-03-22 09:10:35', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4832, 'man0002', 88, 'Pirate\'s Gold', '夺宝船奇', 'slot', 'PiratesGold', NULL, NULL, 103, '2022-03-22 09:16:43', '2022-03-22 09:16:43', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4833, 'man0003', 88, 'Elves Kingdom', '精灵国度', 'slot', 'ElvesKingdom', NULL, NULL, 104, '2022-03-22 09:10:37', '2022-03-22 09:10:37', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4834, 'man0004', 88, 'Taisho Samurai', '大正武士', 'slot', 'TaishoSamurai', NULL, NULL, 105, '2022-03-22 09:10:38', '2022-03-22 09:10:38', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4835, 'man0005', 88, 'Yokai Village', '妖怪村庄', 'slot', 'YokaiVillage', NULL, NULL, 106, '2022-03-22 09:10:39', '2022-03-22 09:10:39', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4836, 'man0006', 88, 'Ninja Master', '忍宗', 'slot', 'NinjaMaster', NULL, NULL, 107, '2022-03-22 09:10:41', '2022-03-22 09:10:41', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4837, 'man0007', 88, 'Bingo Fun', '欢乐宾果', 'slot', 'BingoFun', NULL, NULL, 108, '2022-03-22 09:10:42', '2022-03-22 09:10:42', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4838, 'man0008', 88, 'Carnival Beats', '节奏狂欢', 'slot', 'CarnivalBeats', NULL, NULL, 109, '2022-03-22 09:10:43', '2022-03-22 09:10:43', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4839, 'man0009', 88, 'Bounty Hunter', '赏金超人', 'slot', 'BountyHunter', NULL, NULL, 110, '2022-03-22 09:10:44', '2022-03-22 09:10:44', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4840, 'man0010', 88, 'Treasure Comet', '星际秘宝', 'slot', 'TreasureComet', NULL, NULL, 111, '2022-03-22 09:10:45', '2022-03-22 09:10:45', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4841, 'man0011', 88, 'Cleopatra\'s Code', '艳后的密码', 'slot', 'CleopatrasCode', NULL, NULL, 112, '2022-03-22 09:16:49', '2022-03-22 09:16:49', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4842, 'man0012', 88, 'Fancy Alice', '奇幻艾丽斯', 'slot', 'FancyAlice', NULL, NULL, 113, '2022-03-22 09:10:47', '2022-03-22 09:10:47', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4843, 'man0013', 88, 'Golden Mouse', '新鼠来宝', 'slot', 'GoldenMouse', NULL, NULL, 114, '2022-03-22 09:10:48', '2022-03-22 09:10:48', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4844, 'man0014', 88, 'Fruit Splash', '水果喷发', 'slot', 'FruitSplash', NULL, NULL, 115, '2022-03-22 09:10:49', '2022-03-22 09:10:49', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4845, 'man0015', 88, 'Cougar Roar', '山狮之吼', 'slot', 'CougarRoar', NULL, NULL, 116, '2022-03-22 09:10:50', '2022-03-22 09:10:50', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4846, 'man0016', 88, 'Diamond Storm', '钻不停', 'slot', 'DiamondStorm', NULL, NULL, 117, '2022-03-22 09:10:51', '2022-03-22 09:10:51', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4847, 'man0017', 88, 'Bikini Queens', '比坚尼女王', 'slot', 'BikiniQueens', NULL, NULL, 118, '2022-03-22 09:10:52', '2022-03-22 09:10:52', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4848, 'man0018', 88, 'Bikini Queens Xmas', '比基尼过圣诞', 'slot', 'BikiniQueensXmas', NULL, NULL, 119, '2022-03-22 09:10:53', '2022-03-22 09:10:53', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4849, 'man0019', 88, 'GOLD OF HORUS', '黄金荷鲁斯', 'slot', 'GOLDOFHORUS', NULL, NULL, 120, '2022-03-22 09:10:54', '2022-03-22 09:10:54', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4850, 'man0020', 88, 'Koi Jump', '连连有鱼', 'slot', 'KoiJump', NULL, NULL, 121, '2022-03-22 09:10:55', '2022-03-22 09:10:55', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4851, 'man0021', 88, 'SUSHI CAT', '招财猫寿司', 'slot', 'SUSHICAT', NULL, NULL, 122, '2022-03-22 09:10:56', '2022-03-22 09:10:56', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4852, 'man0022', 88, 'Sweet Paradise', '甜点屋', 'slot', 'SweetParadise', NULL, NULL, 123, '2022-03-22 09:10:57', '2022-03-22 09:10:57', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4853, 'man0023', 88, 'Legend of Atlantis', '亚特兰提斯', 'slot', 'LegendofAtlantis', NULL, NULL, 124, '2022-03-22 09:10:58', '2022-03-22 09:10:58', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4854, 'man0024', 88, 'Dragon X Tiger', '龙虎魂', 'slot', 'DragonXTiger', NULL, NULL, 125, '2022-03-22 09:11:00', '2022-03-22 09:11:00', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4855, 'man0025', 88, 'Bull FA FA', '牛气冲天', 'slot', 'BullFAFA', NULL, NULL, 126, '2022-03-22 09:11:01', '2022-03-22 09:11:01', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4856, 'man0026', 88, 'GEM HUNTER', '宝石猎人', 'slot', 'GEMHUNTER', NULL, NULL, 127, '2022-03-22 09:11:02', '2022-03-22 09:11:02', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4857, 'man0027', 88, 'Ganesha Shine', '象神赐福', 'slot', 'GaneshaShine', NULL, NULL, 128, '2022-03-22 09:11:03', '2022-03-22 09:11:03', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4858, 'man0028', 88, 'Fortune Dragon', '龙行大运2', 'slot', 'FortuneDragon', NULL, NULL, 129, '2022-03-22 09:11:04', '2022-03-22 09:11:04', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4859, 'man0029', 88, '88 FA', '88发', 'slot', '88FA', NULL, NULL, 130, '2022-03-22 09:11:05', '2022-03-22 09:11:05', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4860, 'man0030', 88, 'Muay Thai', '暹罗拳王2', 'slot', 'MuayThai', NULL, NULL, 131, '2022-03-22 09:11:06', '2022-03-22 09:11:06', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4861, 'man0031', 88, 'Bikini Queens Dating', '比基尼女王的约会', 'slot', 'BikiniQueensDating', NULL, NULL, 132, '2022-03-22 09:11:07', '2022-03-22 09:11:07', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4731, 'cmf0001', 89, ' DRAGONBALL FISHING', '龙珠捕鱼', 'fish', 'DRAGONBALLFISHING', NULL, NULL, 2, '2022-03-22 09:08:50', '2022-03-22 09:08:50', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4732, 'cmf0002', 89, 'FISHING OF SAVIORS', '中国神魔捕鱼', 'fish', 'FISHINGOFSAVIORS', NULL, NULL, 3, '2022-03-22 09:08:51', '2022-03-22 09:08:51', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4733, 'cmf0006', 89, 'THE TYRANT AND THE PRINCESS', '中國霸王虞姬', 'fish', 'THETYRANTANDTHEPRINCESS', NULL, NULL, 4, '2022-03-22 09:08:52', '2022-03-22 09:08:52', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4779, 'coo0002', 89, 'Captain Money', '土豪船长', 'fish', 'CaptainMoney', NULL, NULL, 50, '2022-03-22 09:09:41', '2022-03-22 09:09:41', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4780, 'coo0003', 89, 'Inferno Sea', '炼狱海王', 'fish', 'InfernoSea', NULL, NULL, 51, '2022-03-22 09:09:42', '2022-03-22 09:09:42', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4816, 'coo0039', 89, 'Frontier Chaos', '中印大乱斗', 'fish', 'FrontierChaos', NULL, NULL, 87, '2022-03-22 09:10:19', '2022-03-22 09:10:19', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4817, 'coo0040', 89, 'Frontier War', '边境战争', 'fish', 'FrontierWar', NULL, NULL, 88, '2022-03-22 09:10:21', '2022-03-22 09:10:21', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4822, 'coo0045', 89, 'Narcos', '毒枭大乱斗', 'fish', 'Narcos', NULL, NULL, 93, '2022-03-22 09:10:26', '2022-03-22 09:10:26', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4823, 'coo0046', 89, 'Buffalo Thunder', '雷霆野牛', 'fish', 'BuffaloThunder', NULL, NULL, 94, '2022-03-22 09:10:27', '2022-03-22 09:10:27', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4828, 'coo0051', 89, 'Ocean King3 Plus-Crocodile', '海王3 加强版-史前巨鳄', 'fish', 'OceanKing3PlusCrocodile', NULL, NULL, 99, '2022-03-22 09:17:01', '2022-03-22 09:17:01', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4829, 'coo0052', 89, 'Ocean King3 Plus-TitansMonsterverse', '海王3 加强版-泰坦怪獸宇宙', 'fish', 'OceanKing3PlusTitansMonsterverse', NULL, NULL, 100, '2022-03-22 09:17:05', '2022-03-22 09:17:05', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4776, 'cmc0001', 90, 'STOCK STOP', '富途牛牛', 'coc', 'STOCKSTOP', NULL, NULL, 47, '2022-03-22 09:09:37', '2022-03-22 09:09:37', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4777, 'cmc0002', 90, 'COIN OR COMB', '别怂 摇下去就对了', 'coc', 'COINORCOMB', NULL, NULL, 48, '2022-03-22 09:09:38', '2022-03-22 09:09:38', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4786, 'coo0009', 90, 'Football Star', '足球之星', 'coc', 'FootballStar', NULL, NULL, 57, '2022-03-22 09:09:48', '2022-03-22 09:09:48', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4818, 'coo0041', 90, 'Golden Crab', '黄金蟹', 'coc', 'GoldenCrab', NULL, NULL, 89, '2022-03-22 09:10:22', '2022-03-22 09:10:22', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4819, 'coo0042', 90, 'Angel & Devil', '天使恶魔', 'coc', 'AngelDevil', NULL, NULL, 90, '2022-03-22 09:17:14', '2022-03-22 09:17:14', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4820, 'coo0043', 90, 'Captain Domino', '土豪多米诺', 'coc', 'CaptainDomino', NULL, NULL, 91, '2022-03-22 09:10:24', '2022-03-22 09:10:24', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0),
(4821, 'coo0044', 90, 'Skull Bingo', '欢乐骷髅宾果', 'coc', 'SkullBingo', NULL, NULL, 92, '2022-03-22 09:10:25', '2022-03-22 09:10:25', 'tobemaintain', 0, 'enabled', NULL, 'enabled', NULL, 0);


#创建AT游戏注单表
CREATE TABLE `game_order_at`  (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NULL COMMENT '用户ID',
  `order_number` bigint(20) NULL COMMENT '注单号',
  `createdAt` datetime NULL COMMENT '注單建立時間',
  `updatedAt` datetime NULL COMMENT '注單結算時間',
  `player` varchar(20) NULL COMMENT '玩家名稱',
  `playerId` int(10) UNSIGNED NULL COMMENT '玩家 ID',
  `parent` varchar(20) NULL COMMENT '上層名稱',
  `parentId` int(10) UNSIGNED NULL COMMENT '上層 ID',
  `game` varchar(50) NULL COMMENT '遊戲名稱',
  `gameId` varchar(11) NULL COMMENT '遊戲 ID',
  `setId` varchar(20) NULL COMMENT '遊戲盤號',
  `productId` varchar(7) NULL COMMENT '遊戲代號',
  `currency` char(3) NULL COMMENT '下注貨幣',
  `gameType` varchar(10) NULL COMMENT '遊戲類型\r\nslot: 老虎機\r\nfish: 捕漁機\r\ncoc: 街機\r\ncard: 棋牌',
  `status` varchar(10) NULL COMMENT '該局狀態 playing, finish, cancel, finish 為完成狀態',
  `win` int(10) NULL DEFAULT 0 COMMENT '贏分',
  `bet` int(10) NULL DEFAULT 0 COMMENT '注額',
  `validBet` int(10) NULL DEFAULT 0 COMMENT '有效注額',
  `prefix` varchar(10) NULL COMMENT '遊戲前綴',
  `txnId` varchar(50) NULL COMMENT '交易ID',
  `supplier` varchar(10) NULL,
  `supplierPrefix` varchar(10) NULL,
  `endAt` datetime NULL,
  `result` int(10) NULL DEFAULT 0 COMMENT '输赢',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `order_number`(`order_number`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'AT游戏注单';

#添加打码量
ALTER TABLE `user_dml` 
ADD COLUMN `AT` int(10) UNSIGNED NULL DEFAULT 0 AFTER `SGMKBY`,
ADD COLUMN `ATBY` int(10) UNSIGNED NULL DEFAULT 0 AFTER `AT`,
ADD COLUMN `ATJJ` int(10) UNSIGNED NULL DEFAULT 0 AFTER `ATBY`;

#更新AT游戏图片
update game_3th set game_img=CONCAT('https://update.a1jul.com/kgb/game/vert/at/',kind_id,'.png') where game_id in (88,89,90);