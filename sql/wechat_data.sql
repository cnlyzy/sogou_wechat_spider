/*
Navicat MySQL Data Transfer

Source Server         : LocalCentos
Source Server Version : 50718
Source Host           : 127.0.0.1:3306
Source Database       : wechat_data

Target Server Type    : MYSQL
Target Server Version : 50718
File Encoding         : 65001

Date: 2017-06-25 16:38:32
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for wd_mp_library
-- ----------------------------
DROP TABLE IF EXISTS `wd_mp_library`;
CREATE TABLE `wd_mp_library` (
  `id` int(15) unsigned NOT NULL AUTO_INCREMENT,
  `mp_name` varchar(50) CHARACTER SET utf8mb4 DEFAULT '' COMMENT '微信名称',
  `weixinname` varchar(50) CHARACTER SET utf8mb4 DEFAULT '' COMMENT '微信号',
  `original_id` varchar(35) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '公众号原始id',
  `introduce` varchar(255) CHARACTER SET utf8mb4 DEFAULT '' COMMENT '功能介绍',
  `biz` varchar(50) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT 'biz',
  `head_img_url` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '公众号logo',
  `create_time` varchar(11) CHARACTER SET utf8mb4 DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `wd_mp_library_createtime` (`create_time`) USING BTREE,
  KEY `mp_name` (`mp_name`) USING BTREE,
  KEY `index` (`biz`) USING BTREE,
  KEY `weixinname` (`weixinname`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2724 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Table structure for wd_task_count
-- ----------------------------
DROP TABLE IF EXISTS `wd_task_count`;
CREATE TABLE `wd_task_count` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` varchar(20) DEFAULT '0' COMMENT '日期',
  `mp_page` int(11) DEFAULT '0' COMMENT '当天公众号采集多少页',
  `art_page` int(11) DEFAULT '0' COMMENT '当天文章采集多少页',
  `new` int(11) DEFAULT '0' COMMENT '当天入库数',
  `repeat` int(11) DEFAULT '0' COMMENT '当天重复数据',
  `create_time` int(11) DEFAULT '0' COMMENT '记录添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Table structure for wd_task_keyword_count
-- ----------------------------
DROP TABLE IF EXISTS `wd_task_keyword_count`;
CREATE TABLE `wd_task_keyword_count` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `keyword` char(50) DEFAULT '' COMMENT '关键词',
  `mp_count` int(11) DEFAULT '0' COMMENT '公众号入库',
  `art_count` int(11) DEFAULT '0' COMMENT '文章入库',
  `mp_repeat_count` int(11) DEFAULT '0' COMMENT '公众号重复数量',
  `art_repeat_count` int(11) DEFAULT '0' COMMENT '文章重复数量',
  `date` char(20) DEFAULT '' COMMENT '日期',
  `time` int(10) DEFAULT '0' COMMENT '记录添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Table structure for wd_task_keywords
-- ----------------------------
DROP TABLE IF EXISTS `wd_task_keywords`;
CREATE TABLE `wd_task_keywords` (
  `id` int(15) unsigned NOT NULL AUTO_INCREMENT,
  `keyword` varchar(50) CHARACTER SET utf8mb4 DEFAULT '' COMMENT '关键字',
  `create_time` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `index` (`keyword`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
