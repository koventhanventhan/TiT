from .Match import Match


class CricketMatch(Match):
    """
    Points for team 1 are distributed as team1_experience / (team1_experience + team2_experience) * 102.
    Points for team 2 are distributed as team2_experience / (team1_experience + team2_experience) * 102.
    The total sum of points of both teams must be exactly 102.
    If both teams earn exactly 51 points each, then the winner will be the team who had more positive influence in the field throughout the match.
    """

    def distribute_points(self):
        points_to_team1 = self.team1.get_total_experience()
        points_to_team2 = self.team2.get_total_experience()
        
        total_exp = points_to_team1 + points_to_team2
        if total_exp == 0:
            self.team1.points = 51
            self.team2.points = 51
        else:
            self.team1.points = (points_to_team1 / total_exp) * 102
            self.team2.points = (points_to_team2 / total_exp) * 102

    def determine_winner(self):
        if self.team1.points > self.team2.points:
            self.winner = self.team1
        elif self.team2.points > self.team1.points:
            self.winner = self.team2
        else:
            # 51-51 Tie break
            pos1 = sum(1 for p in self.team1.players if p.calculate_influence() > 0)
            pos2 = sum(1 for p in self.team2.players if p.calculate_influence() > 0)
            
            if pos1 > pos2:
                self.winner = self.team1
            elif pos2 > pos1:
                self.winner = self.team2
            else:
                self.winner = None

    def determine_player_of_the_match(self):
        # In this simple model, we use influence as a proxy for individual points if not otherwise specified
        all_players = self.team1.players + self.team2.players
        self.player_of_the_match = max(all_players, key=lambda p: p.calculate_influence())
