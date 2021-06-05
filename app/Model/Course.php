<?php
/**
 * iroha Board Project
 *
 * @author        Kotaro Miura
 * @copyright     2015-2021 iroha Soft, Inc. (https://irohasoft.jp)
 * @link          https://irohaboard.irohasoft.jp
 * @license       https://www.gnu.org/licenses/gpl-3.0.en.html GPL License
 */

App::uses('AppModel', 'Model');

/**
 * Course Model
 *
 * @property Group $Group
 * @property Content $Content
 * @property ContentsQuestion $ContentsQuestion
 * @property Record $Record
 * @property User $User
 */
class Course extends AppModel
{
	public $order = "Course.sort_no"; // デフォルトのソート条件

	/**
	 * バリデーションルール
	 * https://book.cakephp.org/2/ja/models/data-validation.html
	 * @var array
	 */
	public $validate = [
		'title'   => ['notBlank' => ['rule' => ['notBlank']]],
		'sort_no' => ['numeric'  => ['rule' => ['numeric']]]
	];

	/**
	 * アソシエーションの設定
	 * https://book.cakephp.org/2/ja/models/associations-linking-models-together.html
	 * @var array
	 */
	public $hasMany = [
		'Content' => [
			'className' => 'Content',
			'foreignKey' => 'course_id',
			'dependent' => false,
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'exclusive' => '',
			'finderQuery' => '',
			'counterQuery' => ''
		]
	];

	/**
	 * コースの並べ替え
	 * 
	 * @param array $id_list コースのIDリスト（並び順）
	 */
	public function setOrder($id_list)
	{
		for($i=0; $i< count($id_list); $i++)
		{
			$sql = "UPDATE ib_courses SET sort_no = :sort_no WHERE id= :id";

			$params = [
				'sort_no' => ($i + 1),
				'id' => $id_list[$i]
			];

			$this->query($sql, $params);
		}
	}
	
	/**
	 * コースへのアクセス権限チェック
	 * 
	 * @param int $user_id   アクセス者のユーザID
	 * @param int $course_id アクセス先のコースのID
	 * @return bool true: アクセス可能, false : アクセス不可
	 */
	public function hasRight($user_id, $course_id)
	{
		$has_right = false;
		
		$params = [
			'user_id'   => $user_id,
			'course_id' => $course_id
		];
		
		$sql = <<<EOF
SELECT count(*) as cnt
  FROM ib_users_courses
 WHERE course_id = :course_id
   AND user_id   = :user_id
EOF;
		$data = $this->query($sql, $params);
		
		if($data[0][0]['cnt'] > 0)
			$has_right = true;
		
		$sql = <<<EOF
SELECT count(*) as cnt
  FROM ib_groups_courses gc
 INNER JOIN ib_users_groups ug ON gc.group_id = ug.group_id AND ug.user_id   = :user_id
 WHERE gc.course_id = :course_id
EOF;
		$data = $this->query($sql, $params);
		
		if($data[0][0]["cnt"] > 0)
			$has_right = true;
		
		return $has_right;
	}
	
	/**
	 * コースの削除
	 * 
	 * @param int $course_id 削除するコースのID
	 */
	public function deleteCourse($course_id)
	{
		$params = [
			'course_id' => $course_id
		];
		
		// テスト問題の削除
		$sql = "DELETE FROM ib_contents_questions WHERE content_id IN (SELECT id FROM  ib_contents WHERE course_id = :course_id);";
		$this->query($sql, $params);
		
		// コンテンツの削除
		$sql = "DELETE FROM ib_contents WHERE course_id = :course_id;";
		$this->query($sql, $params);
		
		// コースの削除
		$sql = "DELETE FROM ib_courses WHERE id = :course_id;";
		$this->query($sql, $params);
	}
}
