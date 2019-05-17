<?php
namespace App\Controller;

use App\Model\Entity\Role;


/**
 * Profiles Controller
 *
 *
 * @method \App\Model\Entity\Profile[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ProfilesController extends AppController
{
	/**
	 * @var \App\Model\Table\UsersTable;
	 */
	public $Users;

	/**
	 * Add method
	 *
	 * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
	 */
	// Add not needed as profile is built from other tables.
/*	public function add()
	{
		$profile = $this->Profiles->newEntity();
		if ($this->request->is('post')) {
			$profile = $this->Profiles->patchEntity($profile, $this->request->getData());
			if ($this->Profiles->save($profile)) {
				$this->Flash->success(__('The profile has been saved.'));

				return $this->redirect(['action' => 'index']);
			}
			$this->Flash->error(__('The profile could not be saved. Please, try again.'));
		}
		$this->set(compact('profile'));
	}
*/

	/**
	 * Delete method
	 *
	 * @param string|null $id Profile id.
	 * @return \Cake\Http\Response|null Redirects to index.
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
	 */
	// Delete not needed as profile is built from other tables.
/*	public function delete($id = null)
	{
		$this->request->allowMethod(['post', 'delete']);
		$profile = $this->Profiles->get($id);
		if ($this->Profiles->delete($profile)) {
			$this->Flash->success(__('The profile has been deleted.'));
		} else {
			$this->Flash->error(__('The profile could not be deleted. Please, try again.'));
		}

		return $this->redirect(['action' => 'index']);
	}
*/

	/**
	 * Edit method
	 *
	 * @param string|null $id Profile id.
	 *
	 * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
	 */
	public function edit($id = null)
	{
		$profile = $this->Profiles->get($id,
			[
				'contain' => []
			]);
		if ($this->request->is(['patch', 'post', 'put'])) {
			$profile = $this->Profiles->patchEntity($profile, $this->request->getData());
			if ($this->Profiles->save($profile)) {
				$this->Flash->success(__('The profile has been saved.'));

				return $this->redirect(['action' => 'index']);
			}
			$this->Flash->error(__('The profile could not be saved. Please, try again.'));
		}
		$this->set(compact('profile'));
	}

	/**
	 * Index method
	 *
	 * @return \Cake\Http\Response|void
	 */
	public function index()
	{
		$profiles = $this->paginate($this->Users);

		$this->set(compact('profiles'));
	}

	public function initialize(): void
	{
		parent::initialize();

		$this->loadModel('Users');
	}

	/**
	 * View method
	 *
	 * @param string|null $id Profile id.
	 *
	 * @return \Cake\Http\Response|void
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
	 */
	public function view($id = null)
	{
		if ($id === null || $this->identity->role != Role::ADMIN) {
			$id = $this->identity->getIdentifier();
		}

/*
		$query = $this->Users->find()
			->contain(['Couchpotato', 'Nzbget', 'Sabnzb'])
			->contain()
*/
		$profile = $this->Users->get($id,
			[
				'contain' => ['Couchpotato', 'Nzbget', 'Sabnzb']
			]
		);

		$this->set('profile', $profile);
	}
}
